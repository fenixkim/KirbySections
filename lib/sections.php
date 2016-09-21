<?php

namespace Kirby\Sections;

use Str;
use Tpl;
use Pages;
use Page;
use C;
use A;

/**
 * Sections Class.
 *
 * Are like habitual Page objects but it can be rendered in
 * the route of another Page. This class is useful for build "One-Pager" sites.
 * Currently there is an official solution to get something similar using
 * `snippents` at http://getkirby.com/docs/solutions/one-pager but this
 * tecnique do not allows a friendly integration with the Kirby's Panel. 
 *
 * By having a page segmented in sections, you could add a 'Model', a 'Template'
 * and a 'Controller' to each section in a individual way allowing to have a
 * more orderly and consistent site.
 *
 * Sections are differents of templates because its names begin with 'section.', 
 * ie: `section.hero-header.php`.
 *
 * @package default
 * @author fenixkim
 */
class Sections extends Pages {
  
  protected $parent;
  static protected $_controllerCache = array();
  
  function __construct(Page $parent) {
    
    $this->parent = $parent;
    
    // Add to the collection each item as Section
    foreach(static::all($parent) as $page) {
      
      // With model
      if (array_key_exists((string)$page->intendedTemplate(), page::$models)) {
        
        // Get from the parent data cache
        $this->data[$page->id()] = $this->parent->children()->data[$page->id()];
        
      // Without model
      } else {
        $this->data[$page->id()] = new Section($parent, $page->dirname());
      }
    }
  }
    
  public function render() {
    foreach($this->data as $section) static::renderSection($section);
  }
  
  public function renderVisible() {
    foreach($this->visible() as $section) {
      static::renderSection($section);
    }
  }
  
  /**
   * Gets the Pages collection of the current Page excluding the Section objects
   *
   * @return collection
   * @author fenixkim
   */
  public function pages() {
    return $this->parent->children()->filter(function($child) {
      return !str::startsWith($child->template(), static::prefix());
    });
  }
  
  /**
   * Render a specific section
   *
   * @param Section $section The section
   * @param string $data Send data to the template of the section. An optional key/value array 
   * @param string $return Determines if the section should be render or returned
   * @return string
   * @author fenixkim
   */
  public static function renderSection(Section $section, $data = null, $return = false) {
    
    if (!$data) $data = [];
    $controllerData = array();
    $template = (string)$section->template();
    $file = kirby()->roots()->controllers() . DS . "$template.php";
    
    if(file_exists($file)) {
      
      // Gets the callback controller from the cache
      $callback = a::get(static::$_controllerCache, $template);
      
      // Or include once and cache it
      if (!$callback) {
        $callback = include_once($file);
        static::$_controllerCache[$template] = $callback;
      }
      
      if(is_callable($callback)) {
        $controllerData = (array)call_user_func_array($callback, array(
          site(),
          site()->children(),
          $section,
          $data
        ));
      }
    }
    
    $tplData = array_merge(array(
      'kirby'   => kirby(),
      'site'    => site(),
      'pages'   => site()->children(),
      'page'    => $section,
      //'section' => &$section, // Also you could call the fields from $section like $page
    ), $section->templateData(), $data, $controllerData);
    
    return tpl::load($section->templateFile(), $tplData, $return);
  }
  
  /**
   * Gets all the Section objects from a Page object
   *
   * @param Page $page The parent page
   * @return collection Collection of Section objects
   * @author fenixkim
   */
  public static function all(Page $page) {
    return $page->children()->filter(function($child) {
      return static::pageIsSection($child);
    });
  }
  
  /**
   * Count the Section objects in the parent Page
   *
   * @param Page $page The parent Page
   * @return int
   * @author fenixkim
   */
  public static function countSections(Page $page) {
    return static::all($page)->count();
  }
  
  /**
   * Check if a Page object has sections
   *
   * @param Page $page 
   * @return void
   * @author fenixkim
   */
  public static function hasSections(Page $page) {
    return static::countSections($page) > 0;
  }
  
  /**
   * Check if a Page is a Section
   *
   * @param Page $page 
   * @return bool
   * @author fenixkim
   */
  public static function pageIsSection(Page $page) {
    return str::startsWith($page->template(), static::prefix());
  }
  
  /**
   * Avoid direct link for sections
   *
   * @param Page $page The Page of the section
   * @param string $redirectToParent If true, the page will be redirected the the parent page
   * @return void
   * @author fenixkim
   */
  public static function avoidDirectLink($page, $redirectToParent = true, $addHash = true) {
    
    $template = $page->template();
    
    // If the URLs Match is because is a direct link
    if (thisURL() === (string)$page->url()) {
      
      $parent = $page->parent();
      
      if ($parent && $redirectToParent) {
        go($parent->url() . '/' . ($addHash ? '#' . $page->uid() : '') );
      } else {
        go(site()->errorPage());
      }
    }
  }
  
  /**
   * Gets the sections prefix from the config.php file or a 'section.' as default
   *
   * @return void
   * @author fenixkim
   */
  public static function prefix() {
		return c::get('sections.prefix', 'section.');
	}
}