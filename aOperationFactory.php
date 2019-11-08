<?php
/**
 * Project: operation-factory
 * @author bwalker
 */

/**
 * Class aOperationFactory
 *
 * $operations_data = [
 *  [
 *    'operation_action' => 'update',
 *    'operation_type' => 'some_type_1',
 *    ... other operation data
 *  ], [
 *    'operation_action' => 'delete',
 *    'operation_type' => 'some_type_2',
 *    ... other operation data
 *  ], [
 *    'operation_action' => 'delete',
 *    'operation_type' => 'some_type_3',
 *    ... other operation data
 *  ]
 * ];
 *
 */
abstract class aOperationFactory implements iOperation {

  /**
   * Operations collector
   *
   * @var array
   */
  protected $operations = [];

  /**
   * Operations results collector
   *
   * @var array
   */
  protected $operations_results = [];

  /**
   * Test run indicator
   *
   * @var boolean
   */
  private $_dry_run = FALSE;

  /**
   * Errors collector
   *
   * @var array
   */
  private $_errors;

  /**
   * aOperationFactory constructor.
   *
   * @param array $operations_data
   * @param bool $execute
   */
  public function __construct(array $operations_data = [], $execute = TRUE, $dry_run = FALSE) {

    $this->_loadOperationsData($operations_data);

    if ($dry_run) {
      $this->_dry_run = TRUE;
    }

    if ($execute) {
      $this->execute();
    }

  }

  /**
   * Process executable operations.
   *
   * @return array
   */
  public function execute() {
    try {
      if ($this->canExecute()) {
        foreach ($this->operations as $operation) {
          try {
            $method = $operation['method'];
            $this->$method($operation['data']);
            $this->flushEntityCaches();
          } catch (\Exception $e) {
            throw new OperationException($operation['method'], $operation['data'], $e->getMessage(), $e->getCode(), $e);
          }
        }
      }
      return $this->getOperationsResults() + ['errors' => $this->getErrors()];
    } catch (OperationException $e) {
      $this->setError('Operation: ' . $e->getMethod() . ': ' . $e->getMessage());
    }
  }

  /**
   * Converts operstion-results from multi-dimensional array to single-dimensional.
   *
   * @return array
   */
  public function flattenOperationsResults() {
    $results = [];
    foreach ($this->operations_results as $key => $op_results) {
      $results[] = $key;
      $results = array_merge($results, $op_results);
    }
    return $results;
  }

  /**
   * Returns internal errors array.
   *
   * @return array
   */
  public function getErrors() {
    return $this->_errors;
  }

  /**
   * Returns internal operations array.
   *
   * @return array
   */
  public function getOperations() {
    return $this->operations;
  }

  /**
   * Returns internal operations-results array.
   *
   * @return array
   */
  public function getOperationsResults() {
    return $this->operations_results;
  }

  /**
   * Appends operation name and data to internal operations array.
   *
   * @param string $method
   * @param array $data
   */
  protected function addOperation($method, array $data = []) {
    $this->operations[] = compact('method', 'data');
  }

  /**
   * Returns TRUE if there are executable operations.
   *
   * @return bool
   */
  protected function canExecute() {
    return !empty($this->operations);
  }

  /**
   * Internal convenience method to flush Memcache and entity cache.
   * DRUPAL ONLY
   */
  protected function flushEntityCaches() {
    entity_flush_caches();
    module_load_include('inc', 'memcache');
    dmemcache_flush();
  }

  /**
   * Returns TRUE if dry-run property is true.
   *
   * @return boolean
   */
  protected function isDryRun() {
    return $this->_dry_run;
  }

  /**
   * Returns initialized results array.
   *
   * @param string $key
   *
   * @return array
   */
  protected function &prepareResults($key) {
    $this->operations_results[$key] = [];
    return $this->operations_results[$key];
  }

  /**
   * Appends error message to internal errors array.
   *
   * @param string $error
   */
  protected function setError($error) {
    $this->_errors[] = $error;
  }

  /**
   * Generates operation-method from type and action.
   * e.g. operationTypeActionMethod($data)
   *
   * @param string $operation_type
   * @param string $operation_action
   *
   * @return string
   */
  private function _generateOperationMethodName($operation_type, $operation_action) {
    $all = explode('_', "{$operation_type}_{$operation_action}");
    return 'operation' . implode('', array_map('ucwords', $all));
  }

  /**
   * Operations data preprocessor.  Validates and loads operations data.
   *
   * @param array $operations_data
   */
  private function _loadOperationsData(array $operations_data = []) {
    if ($operations_data) {
      foreach ($operations_data as $operation_data) {
        if ($this->_validateOperationData($operation_data)) {
          $method = $this->_generateOperationMethodName($operation_data['operation_type'], $operation_data['operation_action']);
          unset($operation_data['operation_type'], $operation_data['operation_action']);
          $this->addOperation($method, $operation_data);
        }
      }
    }
  }

  /**
   * Returns TRUE if the specified operation-method exists.
   * @param string $operation_method
   *
   * @return bool
   */
  private function _validateOperation($operation_method) {
    return method_exists($this, $operation_method);
  }

  /**
   * Returns TRUE if operation data is valid.
   *
   * @param array $operation_data
   *
   * @return bool
   */
  private function _validateOperationData(array $operation_data) {
    $valid = TRUE;

    if (
      (!isset($operation_data['operation_type'])) ||
      !in_array($operation_data['operation_type'], $this->getOperationTypes())
    ) {
      $valid = FALSE;
      $this->setError("Invalid operation-type: " . check_plain($operation_data['operation_type']));
    }

    if (
      (!isset($operation_data['operation_action'])) ||
      !in_array($operation_data['operation_action'], $this->getOperationActions())
    ) {
      $valid = FALSE;
      $this->setError("Invalid operation-action: " . check_plain($operation_data['operation_action']));
    }

    if ($valid) {
      $valid = $this->_validateOperation($this->_generateOperationMethodName($operation_data['operation_type'], $operation_data['operation_action']));
    }

    return $valid;

  }
}

class OperationException extends \RuntimeException {

  public $operation_method;

  public $operation_data;

  /**
   * OperationException constructor.
   *
   * @inheritDoc
   * @param string $operation_method
   * @param array $operation_data
   * @param string $message
   * @param int $code
   * @param \Throwable|NULL $previous
   */
  public function __construct($operation_method, array $operation_data, $message = "", $code = 0, \Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->operation_method = $operation_method;
    $this->operation_data = $operation_data;
  }

  /**
   * Returns operation-data property value as passed to the exception.
   *
   * @return array
   */
  public function getData() {
    return $this->operation_data;
  }

  /**
   * Returns operation-method property value as passed to the exception.
   *
   * @return string
   */
  public function getMethod() {
    return $this->operation_method;
  }
}