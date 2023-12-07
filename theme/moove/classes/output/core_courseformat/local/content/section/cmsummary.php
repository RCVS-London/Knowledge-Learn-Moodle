<?php 

namespace theme_moove\output\core_courseformat\local\content\section;

use completion_info;
use theme_moove\output\core\output\named_templatable;
use theme_moove\output\core_courseformat\base as course_format;
use theme_moove\output\core_courseformat\output\local\courseformat_named_templatable;
use renderable;
use section_info;
use stdClass;

class course_renderer extends \core_course\output\core_course_renderer {

    public function __construct(course_format $format, section_info $section) {
        $this->format = $format;
        $this->section = $section;
    }

    public function export_for_template(\renderer_base $output): stdClass {

            list($mods, $complete, $total, $showcompletion) = $this->calculate_section_stats();
    
            if (empty($mods)) {
                return new stdClass();
            }
    
            $data = (object)[
                'showcompletion' => $showcompletion,
                'total' => $total,
                'complete' => $complete,
                'mods' => array_values($mods),
            ];
    /*RCVSK to chnage to say complted when section is complete */
            if ($complete != $total){
                $data->modprogress = get_string('progresstotal', 'completion', $data);
            } else {
                $data->modprogress = "PAOLO COMPLETED";
            }
    
            return $data;
        }
}