<?php
/**
 * Project: operation-factory
 * @author bwalker
 */

interface iOperation
{

    /**
     * Process executable operations.
     *
     * @return array
     */
    public function execute();

    /**
     * Returns operation name.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns an array of registered operation types.
     *
     * @return array
     */
    public function getOperationTypes();

    /**
     * Returns an array of registered operation actions.
     *
     * @return array
     */
    public function getOperationActions();
}