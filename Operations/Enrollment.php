<?php
/**
 * Project: operation-factory
 * @author bwalker
 */

/**
 * Class Enrollment
 *
 * Sample operation
 *
 * $operations_data = [
 *  [
 *    'operation_action' => 'delete',
 *    'operation_type' => 'enrollment',
 *    'attendee_id' => 1234,
 *    'session_id' => 1234,
 *  ]
 * ];
 *
 * @package ITRS
 */

namespace Operations;

use \aOperationFactory;

class Enrollment extends aOperationFactory {

    const OPERATION_TYPE_ENROLLMENT = 'enrollment';

    const OPERATION_ACTION_CREATE = 'create';
    const OPERATION_ACTION_DELETE = 'delete';
    const OPERATION_ACTION_TRANSITION = 'transition';
    const OPERATION_ACTION_UPDATE = 'update';

    /**
     * @inheritDoc
     */
    public function getName() {
        return 'enrollment';
    }

    /**
     * @inheritDoc
     */
    public function getOperationActions() {
        return [
            self::OPERATION_ACTION_CREATE,
            self::OPERATION_ACTION_DELETE,
            self::OPERATION_ACTION_TRANSITION,
            self::OPERATION_ACTION_UPDATE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getOperationTypes() {
        return [
            self::OPERATION_TYPE_ENROLLMENT,
        ];
    }
}