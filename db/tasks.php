<?php

/**
 * Task definition file
 *
 * @package     local_user_report
 * @author      Ridwanul Hafiz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_user_report\task\UserReport',
        'blocking' => 0,
        'minute' => '1',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    )
);
