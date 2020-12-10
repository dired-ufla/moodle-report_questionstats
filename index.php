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
 * Report main page
 *
 * @package    report
 * @copyright  2020 Paulo Jr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require __DIR__ . '/../../config.php';
require_once $CFG->libdir . '/adminlib.php';
require_once __DIR__ . '/report_questionstats_categories_form.php';
require_once __DIR__ . '/constants.php';

admin_externalpage_setup('reportquestionstats', '', null, '', array('pagelayout' => 'report'));

$ctype = optional_param('ctype', REPORT_QUESTIONSTATS_ALL_CTYPE, PARAM_INT);

echo $OUTPUT->header();

$mform = new report_questionstats_categories_form();
$mform->display();

$extraQuery = '';

if ($ctype == REPORT_QUESTIONSTATS_FORMAT_CTYPE) {
    $extraQuery = ' AND (Q.questiontext LIKE "%<b>%" OR Q.questiontext LIKE "%<i>%" OR Q.questiontext LIKE "%<li>%") ';
} else if ($ctype == REPORT_QUESTIONSTATS_TABLE_CTYPE) {
    $extraQuery = ' AND Q.questiontext LIKE "%<table>%" ';
} else if ($ctype == REPORT_QUESTIONSTATS_IMAGE_CTYPE) {
    $extraQuery = ' AND Q.questiontext LIKE "%<img%" ';
}

$data = $DB->get_records_sql(
    'SELECT Q.qtype as typename, COUNT(Q.qtype) AS amount FROM {question} AS Q WHERE Q.hidden = 0 AND Q.qtype <> "random"' . $extraQuery . 'GROUP BY Q.qtype'
);
$total = $DB->count_records_sql(
    'SELECT COUNT(Q.id) FROM {question} AS Q WHERE Q.hidden = 0 AND Q.qtype <> "random"' . $extraQuery
);

if ($total > 0) {

    $table = new html_table();
    $table->size = array( '50%', '25%', '25%');
    $table->head = array(get_string('lb_qtype_name', 'report_questionstats'), get_string('lb_question_amount', 'report_questionstats'),
        get_string('lb_question_usage', 'report_questionstats'));

    $chart_labels = array();
    $chart_values = array();

    $ctype_name = '';

    foreach ($data as $item) {
        $row = array();

        $percent = number_format(($item->amount / $total) * 100, 2);

        $ctype_name = get_string('pluginname', 'qtype_' . $item->typename);

        if ($ctype_name == '[[pluginname]]') {
            $ctype_name = 'Tipo de questão desconhecido';
        }

        $chart_labels[] = $ctype_name; 
        $chart_values[] = $percent;

        $row[] = $ctype_name; 
        $row[] = $item->amount;
        $row[] = $percent;
    
        $table->data[] = $row;
    }

    $table->data[] = array(
        get_string('lb_total', 'report_questionstats'), $total, '100.00' 
    );

    if (class_exists('core\chart_pie')) {
        $chart = new core\chart_pie();
        $serie = new core\chart_series(
            get_string('lb_chart_serie', 'report_questionstats'), $chart_values
        );
        $chart->add_series($serie);
        $chart->set_labels($chart_labels);
        echo $OUTPUT->render_chart($chart, false);
    }


    echo html_writer::table($table);
}

echo $OUTPUT->footer();