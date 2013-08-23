<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for Luminis Message Broker plugin
 *
 * @package    enrol_lmb
 * @category   phpunit
 * @copyright  2012 Eric Merrill (merrill@oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/enrol/lmb/lib.php');
require_once($CFG->dirroot . '/enrol/lmb/tests/testlib.php');


/**
 * @group enrol_lmb
 */
class enrol_lmb_lib_testcase extends advanced_testcase {

    public function test_lmb_xml_to_array() {
        $this->resetAfterTest(false);

        $this->assertEquals(enrol_lmb_xml_to_array(lmb_tests_person_xml()), lmb_test_person_expected_array());
    }

    public function test_lmb_xml_to_person() {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        $xmlparams = array();
        lmb_tests_person_merge_defaults($xmlparams);
        $personarray = enrol_lmb_xml_to_array(lmb_tests_person_xml($xmlparams));

        $emailname = explode('@', $xmlparams['email']);
        $emailname = $emailname[0];

        $expected = new stdClass();
        $expected->sourcedidsource = $xmlparams['sourcedidsource'];
        $expected->sourcedid = $xmlparams['sourcedid'];
        $expected->recstatus = $xmlparams['recstatus'];
        $expected->fullname = $xmlparams['fullname'];
        $expected->familyname = $xmlparams['lastname'];
        $expected->givenname = $xmlparams['firstname'];
        $expected->email = $xmlparams['email'];
        $expected->academicmajor = $xmlparams['major'];
        $expected->username = $emailname;
        $expected->nickname = $xmlparams['nickname'];
        $expected->telephone = $xmlparams['phone'];
        $expected->adrstreet = $xmlparams['street'];
        $expected->locality = $xmlparams['city'];
        $expected->region = $xmlparams['region'];
        $expected->country = $xmlparams['country'];
        $expected->timemodified = 1;
        $expected->id = 1;

        $lmb = new enrol_lmb_plugin();
        $lmb->set_config('auth', 'manual');
        $expected->auth = $lmb->get_config('auth');

        // Password settings.
        $lmb->set_config('passwordnamesource', 'none');

        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        $lmb->set_config('passwordnamesource', 'sctid');
        $expected->password = $xmlparams['sctpass'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        $lmb->set_config('passwordnamesource', 'loginid');
        $expected->password = $xmlparams['loginpass'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        $lmb->set_config('passwordnamesource', 'other');
        $lmb->set_config('passworduseridtypeother', 'CustomUserId');
        $expected->password = $xmlparams['custpass'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        // Username Settings.
        // Custom field Settings.
        $lmb->set_config('usernamesource', 'loginid');
        $expected->username = $xmlparams['loginuserid'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        $lmb->set_config('usernamesource', 'sctid');
        $expected->username = $xmlparams['sctuserid'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        $lmb->set_config('usernamesource', 'emailid');
        $expected->username = $xmlparams['emailuserid'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        $lmb->set_config('usernamesource', 'email');
        $expected->username = $xmlparams['email'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        $lmb->set_config('usernamesource', 'emailname');
        $expected->username = $emailname;
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        $lmb->set_config('usernamesource', 'other');
        $lmb->set_config('useridtypeother', 'CustomUserId');
        $expected->username = $xmlparams['custuserid'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        // Custom field Settings.
        $lmb->set_config('customfield1mapping', 1);

        $lmb->set_config('customfield1source', 'loginid');
        $expected->customfield1 = $xmlparams['loginuserid'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        $lmb->set_config('customfield1source', 'sctid');
        $expected->customfield1 = $xmlparams['sctuserid'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        $lmb->set_config('customfield1source', 'emailid');
        $expected->customfield1 = $xmlparams['emailuserid'];
        $result = $this->clean_person_result($lmb->xml_to_person($personarray));
        $this->assertEquals($expected, $result);

        unset($expected->password);
        unset($expected->auth);
        // TODO $params = array('sourcedid' => $xmlparams['sourcedid'], 'sourcedidsource' => $xmlparams['sourcedidsource']);
        $params = array('sourcedid' => $xmlparams['sourcedid']);
        $dbrecord = $this->clean_lmb_object($DB->get_record('enrol_lmb_people', $params));
        $this->assertEquals($expected, $dbrecord);
    }

    public function test_lmb_person_to_moodleuser() {
        // TODO expand for settings, conflicts, etc.
        global $CFG;
        $this->resetAfterTest(true);
        $lmb = new enrol_lmb_plugin();

        $xmlparams = array();
        lmb_tests_person_merge_defaults($xmlparams);
        $personarray = enrol_lmb_xml_to_array(lmb_tests_person_xml($xmlparams));

        $emailname = explode('@', $xmlparams['email']);
        $emailname = $emailname[0];
        $pos = strrpos($xmlparams['nickname'], ' '.$xmlparams['lastname']);
        $nickfirstname = substr($xmlparams['nickname'], 0, $pos);

        $lmbperson = $lmb->xml_to_person($personarray);

        $expected = new stdClass();
        $expected->idnumber = $xmlparams['sourcedid'];
        $expected->auth = $lmb->get_config('auth');
        $expected->id = 1;
        $expected->timemodified = 1;
        $expected->mnethostid = $CFG->mnet_localhost_id;
        $expected->country = $CFG->country;
        $expected->firstname = $xmlparams['firstname'];
        $expected->lastname = $xmlparams['lastname'];
        $expected->email = $xmlparams['email'];
        $expected->username = $emailname;
        $expected->confirmed = 1;
        $expected->address = '';
        $expected->lang = $CFG->lang;

        $lmb->set_config('includetelephone', 0);
        $lmb->set_config('includeaddress', 0);
        $lmb->set_config('includecity', 0);
        $lmb->set_config('nickname', 0);

        $result = $this->clean_user_result($lmb->person_to_moodleuser($lmbperson));
        $this->assertEquals($expected, $result, 'Error in moodle user creation test - without options');

        $this->resetAllData();

        $lmb->set_config('includetelephone', 1);
        $expected->phone1 = $xmlparams['phone'];
        $lmb->set_config('includeaddress', 1);
        $expected->address = $xmlparams['street'];
        $lmb->set_config('includecity', 1);
        $expected->city = $xmlparams['city'];
        $lmb->set_config('nickname', 1);
        $expected->firstname = $nickfirstname;

        $result = $this->clean_user_result($lmb->person_to_moodleuser($lmbperson));
        $this->assertEquals($expected, $result, 'Error in moodle user creation test - with options');

        unset($expected->mnethostid);
        unset($expected->country);
        unset($expected->confirmed);
        unset($expected->lang);

        $lmb->set_config('forcename', 1);
        $lmb->set_config('forceemail', 1);
        $lmb->set_config('forcetelephone', 1);
        $lmb->set_config('forceaddress', 1);
        $lmb->set_config('defaultcity', 'standard');
        $lmb->set_config('standardcity', 'Standard City');
        $expected->city = 'Standard City';

        $result = $this->clean_user_result($lmb->person_to_moodleuser($lmbperson));
        $this->assertEquals($expected, $result, 'Error in forced user tests');

        $lmb->set_config('forcename', 0);
        unset($expected->firstname);
        unset($expected->lastname);
        $lmb->set_config('forceemail', 0);
        unset($expected->email);
        $lmb->set_config('forcetelephone', 0);
        unset($expected->phone1);
        $lmb->set_config('forceaddress', 0);
        $expected->address = '';;
        unset($expected->city);

        $result = $this->clean_user_result($lmb->person_to_moodleuser($lmbperson));
        $this->assertEquals($expected, $result, 'Error in do-not-force user tests');
    }

    public function test_lmb_xml_to_term() {
        global $DB;
        $this->resetAfterTest(true);
        $lmb = new enrol_lmb_plugin();

        $xmlparams = array();
        lmb_tests_term_merge_defaults($xmlparams);
        $termxmlarray = enrol_lmb_xml_to_array(lmb_tests_term_xml($xmlparams));

        $expected = new stdClass();
        $expected->sourcedidsource = 'Test SCT Banner';
        $expected->sourcedid = '201310';
        $expected->title = 'Long Term 201310';
        $expected->starttime = 1357016400;
        $expected->endtime = 1357016400;
        $expected->timemodified = 1;
        $expected->id = 1;

        $result = $this->clean_lmb_object($lmb->xml_to_term($termxmlarray));
        $this->assertEquals($expected, $result);

        // TODO These are not used, and the DB columbs should be dropped at some point.
        $expected->studentshowtime = '0';
        $expected->active = '1';

        $params = array('sourcedidsource' => 'Test SCT Banner', 'sourcedid' => '201310');
        $dbrecord = $this->clean_lmb_object($DB->get_record('enrol_lmb_terms', $params));
        $this->assertEquals($expected, $dbrecord);
    }

    public function test_lmb_xml_to_course() {
        global $DB;

        $this->resetAfterTest(true);
        $lmb = new enrol_lmb_plugin();
        $coursexmlarray = enrol_lmb_xml_to_array(lmb_tests_course_xml());

        $expected = new stdClass();
        $expected->sourcedidsource = 'Test SCT Banner';
        $expected->sourcedid = '10001.201310';
        $expected->coursenumber = '10001';
        $expected->term = '201310';
        $expected->longtitle = 'DEP-101-001';
        $expected->fulltitle = 'Full Course Description';
        $expected->rubric = 'DEP-101';
        $expected->dept = 'DEP';
        $expected->depttitle = 'Department Unit';
        $expected->num = '101';
        $expected->section = '001';
        $expected->startdate = 1357016400;
        $expected->enddate = 1372564800;
        $expected->timemodified = 1;
        $expected->id = 1;

        $result = $this->clean_lmb_object($lmb->xml_to_course($coursexmlarray));
        $this->assertEquals($expected, $result, 'XML to Course');

        $params = array('sourcedidsource' => 'Test SCT Banner', 'sourcedid' => '10001.201310');
        $dbrecord = $this->clean_lmb_object($DB->get_record('enrol_lmb_courses', $params));
        $this->assertEquals($expected, $dbrecord, 'XML to Course DB Record');
    }

    public function test_lmb_course_to_moodlecourse() {
        global $DB;

        $this->resetAfterTest(true);

        $moodlecourseconfig = get_config('moodlecourse');
        $lmb = new enrol_lmb_plugin();
        $coursexmlarray = enrol_lmb_xml_to_array(lmb_tests_course_xml());
        $lmb->xml_to_term(enrol_lmb_xml_to_array(lmb_tests_term_xml()));
        $lmbcourse = $lmb->xml_to_course($coursexmlarray);

        // -----------------------------------------------------------------------------------------
        // Hard coded settings.
        // -----------------------------------------------------------------------------------------
        $lmb->set_config('usemoodlecoursesettings', 0);

        $expected = new stdClass();
        $expected->id = 1;
        $expected->timecreated = 1;
        $expected->timemodified = 1;
        $expected->fullname = 'DEP-101-10001-Full Course Description';
        $expected->shortname = 'DEP101-10001201310';
        $expected->idnumber = '10001.201310';
        $expected->format = 'topics';
        $expected->showgrades = 1;
        $expected->newsitems = 3;
        $expected->startdate = '1357016400';
        $expected->showreports = 1;
        $expected->visible = '1';
        $expected->visibleold = '1';
        $expected->lang = '';
        $expected->theme = '';

        $rawmoodlecourse = $lmb->course_to_moodlecourse($lmbcourse);
        $moodlecourse = $this->clean_course_result(clone $rawmoodlecourse);
        $this->assertEquals($expected, $moodlecourse, 'LMB Course to Moodle Course, hardcoded');

        $params = array('courseid' => $rawmoodlecourse->id, 'name' => 'numsections');
        $numsections = $DB->get_field('course_format_options', 'value', $params);
        $this->assertEquals(6, $numsections, 'Number of sections, hardcoded');

        // -----------------------------------------------------------------------------------------
        // Moodle Course Settings
        // -----------------------------------------------------------------------------------------
        $this->resetAllData();

        $lmb->set_config('usemoodlecoursesettings', 1);
        $lmb->xml_to_term(enrol_lmb_xml_to_array(lmb_tests_term_xml()));

        $expected = new stdClass();
        $expected->id = 1;
        $expected->timecreated = 1;
        $expected->timemodified = 1;
        $expected->fullname = 'DEP-101-10001-Full Course Description';
        $expected->shortname = 'DEP101-10001201310';
        $expected->idnumber = '10001.201310';
        $expected->format = $moodlecourseconfig->format;
        $expected->showgrades = $moodlecourseconfig->showgrades;
        $expected->newsitems = $moodlecourseconfig->newsitems;
        $expected->startdate = '1357016400';
        $expected->showreports = $moodlecourseconfig->showreports;
        $expected->visible = '1';
        $expected->visibleold = '1';
        $expected->lang = $moodlecourseconfig->lang;
        $expected->theme = '';

        $rawmoodlecourse = $lmb->course_to_moodlecourse($lmbcourse);
        $moodlecourse = $this->clean_course_result(clone $rawmoodlecourse);
        $this->assertEquals($expected, $moodlecourse, 'LMB Course to Moodle Course, Moodle settings');

        $params = array('courseid' => $rawmoodlecourse->id, 'name' => 'numsections');
        $numsections = $DB->get_field('course_format_options', 'value', $params);
        $this->assertEquals($moodlecourseconfig->numsections, $numsections, 'Number of sections, Moodle settings');

    }

    public function test_lmb_xml_to_person_memberships() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        $lmb = new enrol_lmb_plugin();
        $membershiparray = enrol_lmb_xml_to_array(lmb_tests_person_member_xml());

        // Convert a membership and check it.
        $expected = array();
        $expected[0] = new stdClass();
        $expected[0]->coursesourcedid = '10001.201310';
        $expected[0]->personsourcedid = 'usersourcedid';
        $expected[0]->term = '201310';
        $expected[0]->role = 1;
        $expected[0]->status = '1';
        $expected[0]->gradable = 1;
        $expected[0]->midtermgrademode = 'Standard Numeric';
        $expected[0]->finalgrademode = 'Standard Numeric';
        $expected[0]->beginrestrict = 0;
        $expected[0]->beginrestricttime = 1367812800;
        $expected[0]->endrestrict = 0;
        $expected[0]->endrestricttime = 1372305599;
        $expected[0]->id = 1;

        $result = $this->clean_array_of_objects($lmb->xml_to_person_memberships($membershiparray));
        $this->assertEquals($expected, $result, 'Single person conversion');

        // Check the DB for the record.
        $expected[0]->extractstatus = '0';
        $expected[0]->succeeded = '0';
        $expected[0]->midtermsubmitted = '0';
        $expected[0]->finalsubmitted = '0';
        $expected[0]->timemodified = 1;

        $params = array('coursesourcedid' => '10001.201310', 'personsourcedid' => 'usersourcedid');
        $dbrecord = $this->clean_lmb_object($DB->get_record('enrol_lmb_enrolments', $params));
        $this->assertEquals($expected[0], $dbrecord, 'Single person conversion DB Record');

        // Convert a multiple membership array.
        $params = array();
        $params['members'][0] = array();
        $params['members'][0]['sourcedidsource'] = 'Test SCT Banner';
        $params['members'][0]['sourcedid'] = 'usersourcedid';
        $params['members'][0]['role'] = '01';
        $params['members'][0]['status'] = '1';
        $params['members'][0]['beginrestrict'] = '0';
        $params['members'][0]['beignrestrictdate'] = '2013-05-06';
        $params['members'][0]['endrestrict'] = '0';
        $params['members'][0]['endrestrictdate'] = '2013-06-26';
        $params['members'][0]['midtermmode'] = 'Standard Numeric';
        $params['members'][0]['finalmode'] = 'Standard Numeric';
        $params['members'][0]['gradable'] = '1';
        $params['members'][1] = array();
        $params['members'][1]['sourcedidsource'] = 'Test SCT Banner';
        $params['members'][1]['sourcedid'] = 'usersourcedid2';
        $params['members'][1]['role'] = '02';
        $params['members'][1]['status'] = '1';
        $membershiparray = enrol_lmb_xml_to_array(lmb_tests_person_member_xml($params));

        $expected = array();
        $expected[0] = new stdClass();
        $expected[0]->coursesourcedid = '10001.201310';
        $expected[0]->personsourcedid = 'usersourcedid';
        $expected[0]->term = '201310';
        $expected[0]->role = 1;
        $expected[0]->status = '1';
        $expected[0]->gradable = 1;
        $expected[0]->midtermgrademode = 'Standard Numeric';
        $expected[0]->finalgrademode = 'Standard Numeric';
        $expected[0]->beginrestrict = 0;
        $expected[0]->beginrestricttime = 1367812800;
        $expected[0]->endrestrict = 0;
        $expected[0]->endrestricttime = 1372305599;
        $expected[0]->id = 1;

        $expected[1] = new stdClass();
        $expected[1]->coursesourcedid = '10001.201310';
        $expected[1]->personsourcedid = 'usersourcedid2';
        $expected[1]->term = '201310';
        $expected[1]->role = 2;
        $expected[1]->status = '1';
        $expected[1]->beginrestrict = 0;
        $expected[1]->beginrestricttime = 0;
        $expected[1]->endrestrict = 0;
        $expected[1]->endrestricttime = 0;
        $expected[1]->id = 1;

        $result = $this->clean_array_of_objects($lmb->xml_to_person_memberships($membershiparray));
        $this->assertEquals($expected, $result, 'Multiple people conversion');

        // Check the DB records.
        $expected[0]->extractstatus = '0';
        $expected[0]->succeeded = '0';
        $expected[0]->midtermsubmitted = '0';
        $expected[0]->finalsubmitted = '0';
        $expected[0]->timemodified = 1;

        $expected[1]->extractstatus = '0';
        $expected[1]->succeeded = '0';
        $expected[1]->gradable = '0';
        $expected[1]->midtermgrademode = null;
        $expected[1]->midtermsubmitted = '0';
        $expected[1]->finalgrademode = null;
        $expected[1]->finalsubmitted = '0';
        $expected[1]->timemodified = 1;

        $params = array('coursesourcedid' => '10001.201310', 'personsourcedid' => 'usersourcedid');
        $dbrecord = $this->clean_lmb_object($DB->get_record('enrol_lmb_enrolments', $params));
        $this->assertEquals($expected[0], $dbrecord, 'Multiple people conversion DB Record 1');

        $params = array('coursesourcedid' => '10001.201310', 'personsourcedid' => 'usersourcedid2');
        $dbrecord = $this->clean_lmb_object($DB->get_record('enrol_lmb_enrolments', $params));
        $this->assertEquals($expected[1], $dbrecord, 'Multiple people conversion DB Record 2');
    }

    public function test_lmb_person_memberships_to_enrolments() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        $this->setup_term();
        $this->setup_course();
        $this->setup_person();

        $lmb = new enrol_lmb_plugin();
        $membershiparray = enrol_lmb_xml_to_array(lmb_tests_person_member_xml());

        $user = $DB->get_record('user', array('idnumber' => 'usersourcedid'));
        $this->assertEquals(true, is_object($user), 'Verify user exists');
        $course = $DB->get_record('course', array('idnumber' => '10001.201310'));
        $this->assertEquals(true, is_object($course), 'Verify course exists');
        $coursecontext = context_course::instance($course->id);

        $result = is_enrolled($coursecontext, $user->id);
        $this->assertEquals(false, $result, 'Verify not enrolled');

        // TODO Enrol, unenrol, restrict - start,end,active,inactive, recover grades
        // TODO Roles.

        // Basic enrol.
        $enrols = $lmb->xml_to_person_memberships($membershiparray);
        $lmb->person_memberships_to_enrolments($enrols);
        $result = is_enrolled($coursecontext, $user->id);
        $this->assertEquals(true, $result, 'Basic enrolled');

        // Basic unenrol.
        $membershiparray['membership']['#']['member'][0]['#']['role'][0]['#']['status'][0]['#'] = 0;
        $enrols = $lmb->xml_to_person_memberships($membershiparray);
        $lmb->person_memberships_to_enrolments($enrols);
        $result = is_enrolled($coursecontext, $user->id);
        $this->assertEquals(false, $result, 'Basic enrolled');

        $lmb->set_config('userestrictdates', 1);
        // Verify restrict = 0 is respected.
        $membershiparray['membership']['#']['member'][0]['#']['role'][0]['#']['timeframe'][0]['#']
                ['begin'][0]['@']['restrict'] = 0;
        $membershiparray['membership']['#']['member'][0]['#']['role'][0]['#']['timeframe'][0]['#']
                ['begin'][0]['#'] = date('Y-m-d', time()+3600);

        $membershiparray['membership']['#']['member'][0]['#']['role'][0]['#']['timeframe'][0]['#']
                ['end'][0]['@']['restrict'] = 0;
        $membershiparray['membership']['#']['member'][0]['#']['role'][0]['#']['timeframe'][0]['#']
                ['end'][0]['#'] = date('Y-m-d', time()+7200);

        $membershiparray['membership']['#']['member'][0]['#']['role'][0]['#']['timeframe'][0]['#']
                ['begin'][0]['@']['restrict'] = 1;
        $membershiparray['membership']['#']['member'][0]['#']['role'][0]['#']['timeframe'][0]['#']
                ['begin'][0]['#'] = date('Y-m-d', time()-3600);

        $membershiparray['membership']['#']['member'][0]['#']['role'][0]['#']['timeframe'][0]['#']
                ['end'][0]['@']['restrict'] = 1;
        $membershiparray['membership']['#']['member'][0]['#']['role'][0]['#']['timeframe'][0]['#']
                ['end'][0]['#'] = date('Y-m-d', time()+3600);
    }

    public function test_lmb_xml_to_xls_memberships() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        $lmb = new enrol_lmb_plugin();
        $xmlmembersarray = enrol_lmb_xml_to_array(lmb_tests_xlist_member_xml());

        $expected = array();
        $expected[0] = new stdClass();
        $expected[0]->succeeded = 0;
        $expected[0]->coursesourcedidsource = 'Test SCT Banner';
        $expected[0]->coursesourcedid = '10001.201310';
        $expected[0]->crosssourcedidsource = 'Test SCT Banner';
        $expected[0]->crosslistsourcedid = 'XLSAA201310';
        $expected[0]->status = '1';
        $expected[0]->type = $lmb->get_config('xlstype');
        $expected[0]->id = 1;

        $expected[1] = new stdClass();
        $expected[1]->succeeded = 0;
        $expected[1]->coursesourcedidsource = 'Test SCT Banner';
        $expected[1]->coursesourcedid = '10002.201310';
        $expected[1]->crosssourcedidsource = 'Test SCT Banner';
        $expected[1]->crosslistsourcedid = 'XLSAA201310';
        $expected[1]->status = '1';
        $expected[1]->type = $lmb->get_config('xlstype');
        $expected[1]->id = 1;

        $result = $this->clean_array_of_objects($lmb->xml_to_xls_memberships($xmlmembersarray));
        $this->assertEquals($expected, $result, 'Multiple XLS records');

        $expected[0]->manual = '0'; // TODO - Is this even used?
        $expected[0]->crosslistgroupid = null; // TODO - Is this even used?
        $expected[0]->timemodified = 1;

        $expected[1]->manual = '0'; // TODO - Is this even used?
        $expected[1]->crosslistgroupid = null; // TODO - Is this even used?
        $expected[1]->timemodified = 1;

        $params = array('coursesourcedidsource' => 'Test SCT Banner', 'coursesourcedid' => '10001.201310',
            'crosssourcedidsource' => 'Test SCT Banner', 'crosslistsourcedid' => 'XLSAA201310');
        $dbrecord = $this->clean_lmb_object($DB->get_record('enrol_lmb_crosslists', $params));
        $this->assertEquals($expected[0], $dbrecord, 'Multiple XLS DB Record 1');

        $params = array('coursesourcedidsource' => 'Test SCT Banner', 'coursesourcedid' => '10002.201310',
            'crosssourcedidsource' => 'Test SCT Banner', 'crosslistsourcedid' => 'XLSAA201310');
        $dbrecord = $this->clean_lmb_object($DB->get_record('enrol_lmb_crosslists', $params));
        $this->assertEquals($expected[1], $dbrecord, 'Multiple XLS DB Record 2');

        // -----------------------------------------------------------------------------------------
        // Check forced type
        // -----------------------------------------------------------------------------------------
        $this->resetAllData();

        $lmb->set_config('xlstype', 'meta');
        $params = array('type' => 'merge');
        $xmlmembersarray = enrol_lmb_xml_to_array(lmb_tests_xlist_member_xml($params));

        $expected = array();
        $expected[0] = new stdClass();
        $expected[0]->succeeded = 0;
        $expected[0]->coursesourcedidsource = 'Test SCT Banner';
        $expected[0]->coursesourcedid = '10001.201310';
        $expected[0]->crosssourcedidsource = 'Test SCT Banner';
        $expected[0]->crosslistsourcedid = 'XLSAA201310';
        $expected[0]->status = '1';
        $expected[0]->type = 'merge';
        $expected[0]->id = 1;

        $expected[1] = new stdClass();
        $expected[1]->succeeded = 0;
        $expected[1]->coursesourcedidsource = 'Test SCT Banner';
        $expected[1]->coursesourcedid = '10002.201310';
        $expected[1]->crosssourcedidsource = 'Test SCT Banner';
        $expected[1]->crosslistsourcedid = 'XLSAA201310';
        $expected[1]->status = '1';
        $expected[1]->type = 'merge';
        $expected[1]->id = 1;

        $result = $this->clean_array_of_objects($lmb->xml_to_xls_memberships($xmlmembersarray));
        $this->assertEquals($expected, $result, 'Multiple XLS records merge');

        $expected[0]->manual = '0'; // TODO - Is this even used?
        $expected[0]->crosslistgroupid = null; // TODO - Is this even used?
        $expected[0]->timemodified = 1;

        $expected[1]->manual = '0'; // TODO - Is this even used?
        $expected[1]->crosslistgroupid = null; // TODO - Is this even used?
        $expected[1]->timemodified = 1;

        $params = array('coursesourcedidsource' => 'Test SCT Banner', 'coursesourcedid' => '10001.201310',
            'crosssourcedidsource' => 'Test SCT Banner', 'crosslistsourcedid' => 'XLSAA201310');
        $dbrecord = $this->clean_lmb_object($DB->get_record('enrol_lmb_crosslists', $params));
        $this->assertEquals($expected[0], $dbrecord, 'Multiple XLS Merge DB Record 1');

        $params = array('coursesourcedidsource' => 'Test SCT Banner', 'coursesourcedid' => '10002.201310',
            'crosssourcedidsource' => 'Test SCT Banner', 'crosslistsourcedid' => 'XLSAA201310');
        $dbrecord = $this->clean_lmb_object($DB->get_record('enrol_lmb_crosslists', $params));
        $this->assertEquals($expected[1], $dbrecord, 'Multiple XLS Merge DB Record 2');

        // TODO check conflict handling. Internal ID issue.
        // TODO meta.

    }

    public function test_lmb_xml_memberships_to_moodlecourse() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        $this->setup_term();
        $this->setup_course();
        $this->setup_person();
    }

    private function clean_user_result($user) {

        return $this->clean_lmb_object($user);
    }

    private function clean_person_result($person) {
        return $this->clean_lmb_object($person);
    }

    private function clean_course_result($course) {
        unset($course->category);
        unset($course->sortorder);
        unset($course->summary);
        unset($course->summaryformat);
        unset($course->sectioncache);
        unset($course->modinfo);
        unset($course->marker);
        unset($course->maxbytes);
        unset($course->legacyfiles);
        unset($course->groupmode);
        unset($course->groupmodeforce);
        unset($course->defaultgroupingid);
        unset($course->requested);
        unset($course->enablecompletion);
        unset($course->completionnotify);

        return $this->clean_lmb_object($course);
    }

    private function clean_array_of_objects($arr) {
        foreach ($arr as $key => $item) {
            $arr[$key] = $this->clean_lmb_object($item);
        }

        return $arr;
    }

    private function clean_lmb_object($obj) {
        if (isset($obj->id)) {
            $obj->id = 1;
        }
        if (isset($obj->timemodified)) {
            $obj->timemodified = 1;
        }
        if (isset($obj->timecreated)) {
            $obj->timecreated = 1;
        }

        return $obj;
    }

    private function setup_term($params = array()) {
        $lmb = new enrol_lmb_plugin();
        $termxmlarray = enrol_lmb_xml_to_array(lmb_tests_term_xml($params));

        $lmb->xml_to_term($termxmlarray);
    }

    private function setup_course($params = array()) {
        $lmb = new enrol_lmb_plugin();
        $coursexmlarray = enrol_lmb_xml_to_array(lmb_tests_course_xml($params));

        $course = $lmb->xml_to_course($coursexmlarray);
        $lmb->course_to_moodlecourse($course);
    }

    private function setup_person($params = array()) {
        $lmb = new enrol_lmb_plugin($params);
        $personxmlarray = enrol_lmb_xml_to_array(lmb_tests_person_xml());

        $person = $lmb->xml_to_person($personxmlarray);
        $lmb->person_to_moodleuser($person);
    }
}
