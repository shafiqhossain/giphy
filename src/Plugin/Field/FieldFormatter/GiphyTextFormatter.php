<?php

namespace Drupal\giphy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;

/**
 * Plugin implementation of the 'giphy_text' formatter.
 *
 * @FieldFormatter(
 *   id = "giphy_text",
 *   module = "giphy",
 *   label = @Translation("Giphy formatter"),
 *   field_types = {
 *     "string",
 *     "text"
 *   }
 * )
 */
class GiphyTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Api Key: '.$this->getSetting('api_key'));
    $summary[] = $this->t('Displays number of search results: '.$this->getSetting('results_number'));
    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Declare a setting named 'results_number' to display search result from giphy.com
      'results_number' => 1,
      'api_key' => '3eFQvabDx69SMoOemSPiYfh9FY0nzO9x',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['api_key'] = [
      '#title' => $this->t('Api Key'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('api_key'),
      '#max_length' => 255,
      '#description' => $this->t('Enter the Api Key from Giphy.'),
    ];

    $element['results_number'] = [
      '#title' => $this->t('Results number'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('results_number'),
      '#max_length' => 15,
      '#description' => $this->t('Total number of search results display in the view.'),
    ];


    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

	//get configuration
    $results_number = !empty($this->getSetting('results_number')) ? intval($this->getSetting('results_number')) : 1;
    $api_key = !empty($this->getSetting('api_key')) ? $this->getSetting('api_key') : '3eFQvabDx69SMoOemSPiYfh9FY0nzO9x';

    foreach ($items as $delta => $item) {
	  $keyword = $item->value;
	  $html = file_get_contents("https://api.giphy.com/v1/gifs/search?api_key=".$api_key."&q=".$keyword."&offset=0&limit=".$results_number);
	  $data = Json::decode($html);

	  $image_list = "<ul>";
	  if(isset($data['data']) && is_array($data['data']) && count($data['data'])) {
	    foreach($data['data'] as $row) {
		  if(isset($row['images']) && is_array($row['images']) && count($row['images'])>0) {
		    foreach($row['images'] as $r) {
		      $img_url = (isset($r['url']) ? $r['url'] : '');
		      $img_width = (isset($r['width']) ? $r['width'] : '100');
		      $img_height = (isset($r['height']) ? $r['height'] : '100');

		      if(!empty($img_url)) {
		        $image_list .= '<li><img src="'.$img_url.'" width="'.$img_width.'" height="'.$img_height.'" /></li>';
		        break;
		      }
		    }

		  }
	    }
	  }
	  $image_list .= "</ul>";

      $elements[$delta] = [
        '#markup' => Markup::create($image_list),
      ];
    }

    return $elements;
  }

}
