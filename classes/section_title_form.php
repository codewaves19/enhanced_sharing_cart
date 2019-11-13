<?php
namespace enhanced_sharing_cart;

require_once(__DIR__ . '/../../../lib/formslib.php');

class section_title_form extends \moodleform   // To create a form you have to create class extending moodleform class 
{
    /**
     * @var array $sections
     */
    private $sections;
    /**
     * @var string $directory
     */
    private $directory;
    /**
     * @var string $path
     */
    private $path;
    /**
     * @var string $courseid
     */
    private $courseid;
    /**
     * @var string $sectionnumber
     */
    private $sectionnumber;
    
    /**
     * section_title_form constructor.
     * @param array $eligible_sections
     */
    public function __construct($directory, $path, $courseid, $sectionnumber, $eligible_sections)
    {
        $this->directory = $directory;
        $this->path = $path;
        $this->courseid = $courseid;
        $this->sectionnumber = $sectionnumber; // destination section number
        $this->sections = $eligible_sections;
        parent::__construct(); 
    }

    //and override definition for including form elements
    public function definition()
    {
        global $PAGE, $USER, $DB;

        $current_section_name = get_section_name($this->courseid, $this->sectionnumber);

        $mform =& $this->_form;

       // $mform->addElement('static', 'cloneareaheading', '', '<h4>' . get_string('cloneareaheading', 'block_enhanced_sharing_cart') . '</h4>');

        $options = range(0, 10);
        $options[0] = get_string('nocreatesection', 'block_enhanced_sharing_cart');
        $mform->addElement('select', 'numsections', get_string('numsections', 'block_enhanced_sharing_cart'), $options);
        $mform->setDefault('numsections', 0);

        //$radioarray = array();
        //$mform->addElement('static', 'description', '', '<h4>' . get_string('conflict_description', 'block_enhanced_sharing_cart') . '</h4>');

        $mform->addElement('radio', 'enhanced_sharing_cart_section', get_string('conflict_no_overwrite', 'block_enhanced_sharing_cart', $current_section_name), null, 0); // &current_section_name title of destination section

        foreach($this->sections as $section)
        {
            $option_title = get_string('conflict_overwrite_title', 'block_enhanced_sharing_cart', $section->name);
            if($section->summary != null)
            {
                $option_title .= '<br><div class="small"><strong>' . get_string('summary') . ':</strong> ' . strip_tags($section->summary) . '</div>';
            }
            $mform->addElement('radio', 'enhanced_sharing_cart_section', $option_title, null, $section->id);
        }
        /* Use 'selectyesno' to show/hide element.
        $mform->addElement('selectyesno', 'selectyesnoexample', 'Select yesno example');
        $mform->setDefault('selectyesnoexample', 0);

        $mform->addElement('text', 'testeqhideif', 'Test eq hideif');
        $mform->setType('testeqhideif', PARAM_TEXT);
        $mform->hideIf('testeqhideif', 'selectyesnoexample', 'eq', 0);*/
        //if ('numsections' !== 0) {
            $mform->hideIf('enhanced_sharing_cart_section', 'numsections', 'neq', 0);
           // $mform->setDefault('enhanced_sharing_cart_section', 1);
        //}
        /*if ()*/
        if (isset($_POST['submit'])) {
            $selected_val = $_POST['numsections'];  // Storing Selected Value In Variable
            echo "You have selected :" . $selected_val;  // Displaying Selected Value
        }
       
        $mform->setDefault('section_title', 0);

        $mform->addElement('hidden', 'directory', $this->directory);
        $mform->setType('directory', PARAM_BOOL);

        $mform->addElement('hidden', 'path', $this->path);
        $mform->setType('path', PARAM_TEXT);

        $mform->addElement('hidden', 'course', $this->courseid);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'section', $this->sectionnumber);
        $mform->setType('section', PARAM_INT);

        $this->add_action_buttons(true, get_string('conflict_submit', 'block_enhanced_sharing_cart'));
        
    }
}