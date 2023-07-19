<?php

/**
 * The schedule Task class file - UserReport
 *
 * @package     mod_user_report
 * @author      Ridwanul Hafiz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_user_report\task;

use core\task\scheduled_task;

require_once(__DIR__.'/../../../../config.php');


defined('MOODLE_INTERNAL') || die();

class UserReport extends scheduled_task {


    public function get_name()
    {
        // TODO: Implement get_name() method.
        return get_string('taskname', 'mod_user_report');
    }

    public function execute($testing = null)
    {
        global $DB;
        global $OUTPUT;


        $startTime = '2000-01-01 00:00:00';

        $rv = array(
            'active_users' => 0,
            'concurrent_users' => 0,
        );

        // Last Report Log
        $lastReport = $DB->get_record_sql("SELECT * FROM `mdl_report_concurrent_users` where report_sent=1 order by id desc", []);
        if($lastReport){
            $startTime = $lastReport->last_report_sent_at;
        }
        $startTime = strtotime($startTime);


        // Active Users
        $activeUsers = $DB->get_record_sql("SELECT COUNT(id) as total FROM `mdl_logstore_standard_log` WHERE `action` = 'loggedin' AND `timecreated` >= $startTime GROUP BY userid", []);
        if($activeUsers){
            $rv['active_users'] = $activeUsers->total;
        }


        // Concurrent Users
        $nowTmp = strtotime(date('Y-m-d H:i:s'));
        $nowTmpRange = $nowTmp - 600; // 10 minutes
        $concurrentUsers = $DB->get_record_sql("SELECT COUNT(id) as total FROM `mdl_user` WHERE lastlogin < $nowTmp AND `lastaccess` > $nowTmpRange", []);
        if($concurrentUsers){
            $rv['concurrent_users'] = $concurrentUsers->total;
        }


        $reportLog = $DB->get_record_sql("SELECT * FROM `mdl_report_concurrent_users` where report_sent=0 order by id desc", []);
        if($reportLog){

            $report = array(
                'id' => $reportLog->id,
                'active_users' => $rv['active_users'],
                'concurrent_users' => $rv['concurrent_users'],
                'last_report_logged_at' => date('Y-m-d H:i:s'),
                'report_sent' => !$lastReport ? 1 : 0,
                'last_report_sent_at' => !$lastReport ? date('Y-m-d H:i:s') : null,
            );
            $DB->update_record('report_concurrent_users', $report);

        } else {
            $report = new \stdClass();
            $report->active_users = $rv['active_users'];
            $report->concurrent_users = $rv['concurrent_users'];
            $report->last_report_logged_at = date('Y-m-d H:i:s');
            $DB->insert_record('report_concurrent_users', $report);
        }

        if($lastReport){

            $last_report_sent_at = $lastReport->last_report_sent_at;
            $from_time = strtotime($last_report_sent_at);
            $to_time = $nowTmp;
            $diff_minutes = round(abs($from_time - $to_time) / 60,2); // Minutes

            // 20min Differences
            if($diff_minutes > 20){
                $messageBody = $OUTPUT->render_from_template('local_user_report/user_report_email', $rv);
                $admin = $DB->get_record_sql("SELECT * FROM `mdl_user` where id=3", []);
                if ($admin) {
                    email_to_user($admin, 'moodle@thethemeai.com', 'Moodle User Report', html_to_text($messageBody), $messageBody);
                }
            }
        }

    }

}
