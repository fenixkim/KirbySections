<?php

namespace Kirby\Sections;

use Page;
use Str;

/**
 * A Page subclass with the render method
 *
 * @package default
 * @author fenixkim
 */
class Section extends Page {
  
  /**
   * Render a page
   *
   * @param string $data 
   * @param string $return 
   * @return void
   * @author fenixkim
   */
  public function render($data = null, $return = false) {
    return Sections::renderSection($this, $data, $return);
  }
  
  // @TODO Add this method directly on the object
  public function avoidDirectLink($redirectToParent = true, $addHash = true) {
    Sections::avoidDirectLink($this);
  }
}