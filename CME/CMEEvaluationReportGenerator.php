<?php

require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Site/SiteApplication.php';
require_once 'Inquisition/dataobjects/InquisitionResponseWrapper.php';
require_once 'CME/dataobjects/CMEProvider.php';
require_once 'CME/dataobjects/EvaluationWrapper.php';
require_once 'CME/dataobjects/EvaluationReport.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEEvaluationReportGenerator
{
	// {{{ protected properties

	/**
	 * @var SwatDate
	 */
	protected $start_date;

	/**
	 * @var SwatDate
	 */
	protected $end_date;

	/**
	 * @var CMEProvider
	 */
	protected $provider;

	/**
	 * @var SiteApplication
	 */
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app,
		CMEProvider $provider, $year, $quarter)
	{
		$this->app = $app;
		$this->provider = $provider;

		$start_month = ((intval($quarter) - 1) * 3) + 1;

		$this->start_date = new SwatDate();
		$this->start_date->setTime(0, 0, 0);
		$this->start_date->setDate($year, $start_month, 1);
		$this->start_date->setTZ($this->app->default_time_zone);

		$this->end_date = clone $this->start_date;
		$this->end_date->addMonths(3);
	}

	// }}}

	// data retrieval methods
	// {{{ protected function getResponses()

	protected function getResponses()
	{
		$sql = sprintf(
			'select InquisitionResponse.* from AccountEarnedCMECredit
				inner join Account
					on AccountEarnedCMECredit.account = Account.id
				inner join CMECredit
					on AccountEarnedCMECredit.credit = CMECredit.id
				inner join CMEFrontMatter
					on CMECredit.front_matter = CMEFrontMatter.id
				inner join InquisitionResponse
					on AccountEarnedCMECredit.account =
						InquisitionResponse.account
					and CMEFrontMatter.evaluation =
						InquisitionResponse.inquisition
			where CMEFrontMatter.provider = %s
				and convertTZ(earned_date, %s) >= %s
				and convertTZ(earned_date, %s) < %s
				and Account.delete_date is null',
			$this->app->db->quote($this->provider->id, 'integer'),
			$this->app->db->quote($this->app->config->date->time_zone, 'text'),
			$this->app->db->quote($this->start_date->getDate(), 'date'),
			$this->app->db->quote($this->app->config->date->time_zone, 'text'),
			$this->app->db->quote($this->end_date->getDate(), 'date')
		);

		$responses = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('InquisitionResponseWrapper')
		);

		// efficiently load response values
		$values = $responses->loadAllSubRecordsets(
			'values',
			SwatDBClassMap::get('InquisitionResponseValueWrapper'),
			'InquisitionResponseValue',
			'response'
		);

		// efficiently load response value question bindings
		$question_binding_sql =
			'select * from InquisitionInquisitionQuestionBinding
			where id in (%s)';

		$question_bindings = $values->loadAllSubDataObjects(
			'question_binding',
			$this->app->db,
			$question_binding_sql,
			SwatDBClassMap::get('InquisitionInquisitionQuestionBindingWrapper')
		);

		// and questions
		$question_sql = 'select * from InquisitionQuestion where id in (%s)';
		$questions = $question_bindings->loadAllSubDataObjects(
			'question',
			$this->app->db,
			$question_sql,
			SwatDBClassMap::get('InquisitionQuestionWrapper')
		);

		// efficiently load evaluations
		$evaluation_sql = 'select * from Inquisition where id in (%s)';
		$evaluations = $responses->loadAllSubDataObjects(
			'inquisition',
			$this->app->db,
			$evaluation_sql,
			SwatDBClassMap::get('CMEEvaluationWrapper')
		);

		// efficiently load question bindings
		$wrapper = SwatDBClassMap::get(
			'InquisitionInquisitionQuestionBindingWrapper'
		);

		$sql = sprintf(
			'select * from InquisitionInquisitionQuestionBinding
			where inquisition in (%s)
			order by inquisition, displayorder',
			$this->app->db->implodeArray($evaluations->getIndexes(), 'integer')
		);

		$question_bindings = SwatDB::query($this->app->db, $sql, $wrapper);
		$evaluations->attachSubRecordset(
			'question_bindings',
			$wrapper,
			'inquisition',
			$question_bindings
		);

		// efficiently load questions
		$question_sql = 'select * from InquisitionQuestion where id in (%s)';
		$questions = $question_bindings->loadAllSubDataObjects(
			'question',
			$this->app->db,
			$question_sql,
			SwatDBClassMap::get('InquisitionQuestionWrapper')
		);

		// efficiently evaluation question options
		$options = $questions->loadAllSubRecordsets(
			'options',
			SwatDBClassMap::get('InquisitionQuestionOptionWrapper'),
			'InquisitionQuestionOption',
			'question',
			'',
			'displayorder'
		);

		$response_array = array();
		foreach ($responses as $response) {
			// filter out responses for evaluations with no questions
			if (count($response->inquisition->question_bindings) > 0) {
				$response_array[] = $response;
			}
		}

		return $response_array;
	}

	// }}}

	// output methods
	// {{{ public function saveFile()

	public function saveFile($filename)
	{
		if (!file_exists(dirname($filename))) {
			mkdir(dirname($filename), 0770, true);
		}

		$this->initDOMPDF();

		ob_start();
		$this->display();
		$xhtml = ob_get_clean();

		$dompdf = new DOMPDF();
		$dompdf->load_html($xhtml);
		$dompdf->render();

		file_put_contents($filename, $dompdf->output());
	}

	// }}}
	// {{{ protected function initDOMPDF()

	protected function initDOMPDF()
	{
		if (!class_exists('DOMPDF')) {
			define('DOMPDF_ENABLE_PHP', true);
			include_once 'dompdf/dompdf_config.inc.php';
		}
	}

	// }}}

	// report display methods
	// {{{ abstract protected function getTitle()

	abstract protected function getTitle();

	// }}}
	// {{{ protected function display()

	protected function display()
	{
		echo '<!DOCTYPE html>';
		echo '<html>';
		echo '<head>';

		$title = new SwatHtmlTag('title');
		$title->setContent($this->getTitle());
		$title->display();

		$this->displayStyles();

		echo '</head>';
		echo '<body>';

		$this->displayFooter();

		$this->displayEvaluations($this->getResponses());

		echo '</body>';
		echo '</html>';
	}

	// }}}
	// {{{ protected function displayFooter()

	protected function displayFooter()
	{
		$script_tag = new SwatHtmlTag('script');
		$script_tag->type = 'text/php';
		$script_tag->setContent('
			// <![CDATA[
			if (isset($pdf)) {
				$footer = $pdf->open_object();

				$font = Font_Metrics::get_font("arial");
				if (!$font) {
					$font = Font_Metrics::get_font("helvetica");
				}
				if (!$font) {
					$font = Font_Metrics::get_font("sans-serif");
				}

				$black        = array(0, 0, 0);
				$size         = 9;
				$metrics_text = CME::_("Page 1 of 1");

				if ($PAGE_NUM > 9) {
					$metrics_text.= "0";
				}
				if ($PAGE_NUM > 99) {
					$metrics_text.= "0";
				}
				if ($PAGE_COUNT > 9) {
					$metrics_text.= "0";
				}
				if ($PAGE_COUNT > 99) {
					$metrics_text.= "0";
				}

				$text        = CME::_("Page {PAGE_NUM} of {PAGE_COUNT}");

				$width = Font_Metrics::get_text_width(
					$metrics_text,
					$font,
					$size
				);

				$height      = Font_Metrics::get_font_height($font, $size);
				$margin      = 54;
				$page_width  = $pdf->get_width();
				$page_height = $pdf->get_height();

				$pdf->line(
					$margin,
					$page_height - $margin - $height - 12,
					$page_width - $margin,
					$page_height - $margin - $height - 12,
					$black,
					1
				);

				$x = ($page_width - $width) / 2;
				$y = $page_height - $margin - $height;

				$pdf->page_text($x, $y, $text, $font, $size, $black);

				$pdf->close_object();
				$pdf->add_object($footer, "all");
			}
			// ]]>
		', 'text/xml');
		$script_tag->display();
	}

	// }}}
	// {{{ protected function displayStyles()

	protected function displayStyles()
	{
		echo '<style>';
		echo <<<STYLESHEET

body {
	font-size: 11pt;
	font-family: Arial, Helvetica, sans-serif;
}

@page {
	margin: 0.75in;
}

.page {
	page-break-after: always;
}

h1, h2 {
	text-align: center;
	font-size: 18pt;
	margin: 0 0 0.25in 0;
}

h2 {
	font-size: 16pt;
}

h3 {
	margin: 0 0 0.02in 0;
	font-size: 13pt;
}

p, ul {
	margin: 0;
	padding: 0 0 0.2in 0;
}

ul {
	padding: 0 0 0 2em;
	margin-before: 0;
	margin-after: 0;
	padding-start: 0;
	-moz-margin-before: 0;
	-moz-margin-after: 0;
	-moz-padding-start: 0;
	-webkit-margin-before: 0;
	-webkit-margin-after: 0;
	-webkit-padding-start: 0;

}

li, ul {
	list-style-type: none;
}

li {
	margin: 0;
	padding-top: 0.15in;
}

ul ul li, ul ul {
	list-style-type: disc;
}

.question {
	page-break-inside: avoid;
	padding-bottom: 0.4in;
}

.question p {
	margin: 0;
	padding: 0;
}

.bar {
	padding: 0.05in 0;
}

.bar-incomplete {
	background: #ddd;
	border-bottom: 0.02in solid #ddd;
	width: 5in;
	height: 0.1in;
}

.bar-complete {
	background: #46c;
	border-bottom: 0.02in solid #239;
	height: 0.1in;
}

STYLESHEET;

		echo '</style>';
	}

	// }}}
	// {{{ protected function displayEvaluations()

	protected function displayEvaluations(array $responses)
	{
		echo '<div class="page">';

		$this->displayTitle();

		$response_values_by_bodytext = array();
		$questions = array();

		foreach ($responses as $response) {
			foreach ($response->values as $response_value) {
				$question = $response_value->question_binding->question;
				$bodytext = $question->bodytext;
				$bodytext = strip_tags($bodytext);
				$bodytext = str_replace('&nbsp;', '', $bodytext);

				if (!isset($questions[$bodytext])) {
					$questions[$bodytext] = $question;
				}

				if (!isset($response_values_by_bodytext[$bodytext])) {
					$response_values_by_bodytext[$bodytext] = array();
				}

				$response_values_by_bodytext[$bodytext][] = $response_value;
			}
		}

		// sort the responses by the alphabetical order of their questions
		ksort($response_values_by_bodytext, SORT_STRING);

		$index = 1;
		foreach ($response_values_by_bodytext as
			$bodytext => $response_values) {
			$question = $questions[$bodytext];

			// Only show responses for questions that are enabled, or have
			// response values in that month. If a question was turned off, we
			// still want old responses to show up, but there is no point in
			// showing a disabled question once it no longer has responses in a
			// quarter.
			if ($question->enabled === true ||
				count($response_values) > 0) {
				$this->displayQuestion($question, $index++, $response_values);
			}
		}

		echo '</div>';
	}

	// }}}
	// {{{ protected function displayTitle()

	protected function displayTitle()
	{
		$end_date = clone $this->end_date;
		$end_date->subtractMonths(1);

		$header = new SwatHtmlTag('h1');
		$header->setContent(
			sprintf(
				CME::_('Program Evaluation Report for %s to %s'),
				$this->start_date->formatLikeIntl('MMMM yyyy'),
				$end_date->formatLikeIntl('MMMM yyyy')
			)
		);
		$header->display();
	}

	// }}}
	// {{{ protected function displayQuestion()

	protected function displayQuestion(InquisitionQuestion $question, $index,
		array $response_values)
	{
		echo  '<div class="question">';

		$header = new SwatHtmlTag('h3');
		$header->setContent(
			sprintf(
				CME::_('Question %s'),
				$index
			)
		);
		$header->display();

		switch ($question->question_type) {
		case InquisitionQuestion::TYPE_CHECKBOX_LIST:
			$this->displayCheckboxListQuestion($question, $response_values);
			break;
		case InquisitionQuestion::TYPE_RADIO_LIST:
			$this->displayRadioListQuestion($question, $response_values);
			break;
		case InquisitionQuestion::TYPE_FLYDOWN:
			$this->displayFlydownQuestion($question, $response_values);
			break;
		case InquisitionQuestion::TYPE_RADIO_ENTRY:
			$this->displayRadioEntryQuestion($question, $response_values);
			break;
		case InquisitionQuestion::TYPE_TEXT:
			$this->displayTextQuestion($question, $response_values);
			break;
		}

		echo '</div>';
	}

	// }}}
	// {{{ protected function displayRadioEntryQuestion()

	protected function displayRadioEntryQuestion(
		InquisitionQuestion $question, array $response_values)
	{
		$locale = SwatI18NLocale::get();

		echo $question->bodytext;

		echo '<ul>';

		$option_counts = array();
		$option_values = array();
		$total_count = 0;

		if (count($response_values) > 0) {
			foreach ($response_values as $value) {
				$option_text = $value->question_option->title;
				if (!isset($option_counts[$option_text])) {
					$option_counts[$option_text] = 0;
				}
				$option_counts[$option_text]++;
				if ($value->text_value != '') {
					if (!isset($option_values[$option_text])) {
						$option_values[$option_text] = array();
					}
					$option_values[$option_text][] = $value->text_value;
				}
				$total_count++;
			}
			foreach ($question->options as $option) {
				if (isset($option_counts[$option->title])) {
					$percent = $option_counts[$option->title] / $total_count;
					$li_tag = new SwatHtmlTag('li');
					$li_tag->setContent(
						sprintf(
							'%s - %s%% (%s)',
							$option->title,
							$locale->formatNumber(round($percent * 1000) / 10),
							$locale->formatNumber(
								$option_counts[$option->title]
							)
						)
					);
					$li_tag->open();
					$li_tag->displayContent();
					$this->displayBar($percent);
					if (isset($option_values[$option->title])) {
						echo '<ul>';
						foreach ($option_values[$option->title] as $text) {
							$li_tag = new SwatHtmlTag('li');
							$li_tag->setContent($text);
							$li_tag->display();
						}
						echo '</ul>';
					}
					$li_tag->close();
				}
			}
		} else {
			$li_tag = new SwatHtmlTag('li');
			$li_tag->setContent(
				CME::_('There were no responses for this question.')
			);
			$li_tag->display();
		}

		echo '</ul>';
	}

	// }}}
	// {{{ protected function displayCheckboxListQuestion()

	protected function displayCheckboxListQuestion(
		InquisitionQuestion $question, array $response_values)
	{
		$this->displayRadioEntryQuestion($question, $response_values);
	}

	// }}}
	// {{{ protected function displayRadioListQuestion()

	protected function displayRadioListQuestion(
		InquisitionQuestion $question, array $response_values)
	{
		$this->displayRadioEntryQuestion($question, $response_values);
	}

	// }}}
	// {{{ protected function displayFlydownQuestion()

	protected function displayFlydownQuestion(
		InquisitionQuestion $question, array $response_values)
	{
		$this->displayRadioListQuestion($question, $response_values);
	}

	// }}}
	// {{{ protected function displayTextQuestion()

	protected function displayTextQuestion(
		InquisitionQuestion $question, array $response_values)
	{
		// Don't display the response text for now. We require an answer for
		// every text question. As a consequence it means we'll end up
		// displaying A LOT of text. We don't want to do that right now.
		echo $question->bodytext;

		$p_tag = new SwatHtmlTag('p');
		$p_tag->setContent(
			CME::_('Comments are not listed in this report.')
		);
		$p_tag->display();

		/*
		if (count($response_values) > 0) {
			$p_tag = new SwatHtmlTag('p');
			$p_tag->setContent(
				CME::_('The following answers were provided:')
			);
			$p_tag->display();

			echo '<ul>';
			$count = 0;
			foreach ($response_values as $value) {
				$li_tag = new SwatHtmlTag('li');
				$li_tag->setContent($value->text_value);
				$li_tag->display();
				if ($count++ > 25) break;
			}
			echo '</ul>';
		} else {
			$p_tag = new SwatHtmlTag('p');
			$p_tag->setContent(
				CME::_('There were no responses for this question.')
			);
			$p_tag->display();
		}
		*/
	}

	// }}}
	// {{{ protected function displayBar()

	protected function displayBar($percent)
	{
		$percent = round($percent * 1000) / 10;

		echo '<div class="bar">';
		echo '<div class="bar-incomplete">';

		$bar_complete = new SwatHtmlTag('div');
		$bar_complete->class = 'bar-complete';
		$bar_complete->setContent('');
		$bar_complete->style = 'width: '.$percent.'%;';
		$bar_complete->display();

		echo '</div>';
		echo '</div>';
	}

	// }}}
}

?>
