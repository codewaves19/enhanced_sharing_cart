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
 *  Enhanced Sharing Cart - Restore Operation
 *
 *  @package    block_enhanced_sharing_cart
 *  @copyright  2017 (C) VERSION2, INC.
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../config.php';

require_once __DIR__ . '/classes/controller.php';
require_once __DIR__ . '/classes/renderer.php';
require_once __DIR__ . '/classes/section_title_form.php';

$directory = required_param('directory', PARAM_BOOL); // directory 1 if copying a directory parameter is boolean
$id = null;
$path = null;

// Info about the data to be copied from block into the section
if ($directory)
{
    $path = required_param('path', PARAM_TEXT);
} else {
    $id = required_param('id', PARAM_INT);
}
$courseid = required_param('course', PARAM_INT);

$newsectioncreated = null;

$section = required_param('section', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$lastsectionnum = $DB->get_field('course_sections', 'MAX(section)', array('course' => $courseid), MUST_EXIST);

$newsectioncreated = $lastsectionnum + 1;

$sectionnumber = required_param('section', PARAM_INT);

if ($courseid == SITEID) {
    $returnurl = new moodle_url('/');
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $courseid));
}

$returnurl .= '#section-' . $sectionnumber;

require_login($courseid);

try {
    $controller = new enhanced_sharing_cart\controller();

    if ($directory)
    { 
        $form = new \enhanced_sharing_cart\section_title_form($directory, $path, $courseid, $sectionnumber, array());

        if ($form->is_cancelled()) {
            redirect($returnurl);
            exit;
        }

        $use_sc_section = optional_param('numsections', -1, PARAM_INT);
        $numsections = optional_param('numsections', null, PARAM_INT);
        $overwrite = optional_param('enhanced_sharing_cart_section', null, PARAM_INT);

        if ($path[0] == '/') {
            $path = substr($path, 1);
        }
        if ($use_sc_section < 0) 
        {
            $sections = $controller->get_path_sections($path, $courseid, $sectionnumber);

            if (count($sections) > 0) 
            {   // destination or the targeted section
                
                $dest_section = $DB->get_record('course_sections', array('course' => $courseid, 'section' => $sectionnumber)); // get all data copied
            
                $PAGE->set_pagelayout('standard'); // pagelayout = standard
                $PAGE->set_url('/blocks/enhanced_sharing_cart/restore.php');
                $PAGE->set_title(get_string('pluginname', 'block_enhanced_sharing_cart') . ' - ' . get_string('restore', 'block_enhanced_sharing_cart')); 
                $PAGE->set_heading(get_string('restore', 'block_enhanced_sharing_cart'));
                $PAGE->navbar
                    ->add(get_section_name($courseid, $sectionnumber), new moodle_url("/course/view.php?id={$courseid}#section-{$sectionnumber}"))
                    ->add(get_string('pluginname', 'block_enhanced_sharing_cart'))
                    ->add(get_string('restore', 'block_enhanced_sharing_cart'));

                echo $OUTPUT->header();
               // echo $OUTPUT->heading(get_string('section_name_conflict', 'block_enhanced_sharing_cart'));
               // echo $OUTPUT->heading(get_string('section_copy_heading', 'block_enhanced_sharing_cart'));

                $form = new \enhanced_sharing_cart\section_title_form($directory, $path, $courseid, $sectionnumber, $sections);

                $form->display();
                echo $OUTPUT->footer();
                exit;
            }
            $use_sc_section = 0;
        }
        
        if ($use_sc_section > -1)
        {
            $sections = $controller->get_path_sections($path, $courseid, $sectionnumber);
            if ($numsections == 0) {
                    $controller->restore_directory($path, $courseid, $sectionnumber, $overwrite);
            } else {
                $overwrite = optional_param('enhanced_sharing_cart_section',0, PARAM_INT);
                for ($i = 1; $i <= $numsections; $i++) {
                    $controller->create_section($courseid, $newsectioncreated, $sections, $section);
                    $sectionnumber = required_param('section', PARAM_INT) + 1; // destination section
                    $controller->restore_directory($path, $courseid, $sectionnumber, $overwrite);
                    $newsectioncreated++;
                }
            }
        }
    } else {
        $controller->restore($id, $courseid, $sectionnumber);
    }

    redirect($returnurl);
} catch (enhanced_sharing_cart\exception $ex) {
    print_error($ex->errorcode, $ex->module, $returnurl, $ex->a);
} catch (Exception $ex) {
    if (!empty($CFG->debug) and $CFG->debug >= DEBUG_DEVELOPER) {
        print_error('notlocalisederrormessage', 'error', '', $ex->__toString());
    } else {
        print_error('unexpectederror', 'block_enhanced_sharing_cart', $returnurl);
    }
}
