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
 * monitor related strings
 *
 * @package monitor
 * @copyright 2018 Uemanet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Lucas S. Vieira <lucassouzavieiraengcomp@gmail.com>
 */

$string['pluginname'] = 'Monitor';

// Description strings.
$string['paramtimebetweenclicks'] = 'Time between clicks ';
$string['paramstartdate'] = 'Start date query: Y-m-d ';
$string['paramend_date'] = 'End date query: Y-m-d ';
$string['paramtutorid'] = 'Tutor ID';
$string['paramgroupid'] = 'Group ID';

// Return strings.
$string['returnid'] = 'Tutor ID from Moodle';
$string['returnfullname'] = 'Tutor full name';
$string['returnonlinetime'] = 'Online time in seconds';
$string['returndate'] = 'Date';

// Errors strings.
$string['timebetweenclickserror'] = 'Time between clicks should be greater than 0';
$string['startdateerror'] = 'Start date should be less than end date';
$string['enddateerror'] = 'End date should be equals to or less than today date';
$string['databaseaccesserror'] = 'Error on trying access database';
$string['tutornonexistserror'] = 'The pes_id given do not match with an know tutor';
