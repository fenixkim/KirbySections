<?php

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
 * Sections are differents of templates because its names begin with '_', 
 * ie: `_hero-header.php`
 *
 * @package default
 * @author fenixkim
 */
class Sections extends Pages {
  
  protected $parent;
  static protected $_controllerCache = array();
  
  /**
   * Creates a section collection
   * This fetch all sub-pages which starts with `_` as a Section object
   *
   * Usage:
   *
   * For an structure like this:
   *
   * <code>
   * home
   *  - _hero-header
   *  - _newsletter
   *  - _contact
   *  subpage-1
   *  subpage-2
   *
   * // `_hero-header`, `_newsletter` and `_contact` are the sections
   * // `subpage-1` and `subpage-2` are normal subpages
   * <code>
   *
   * You could fetch all the sections of `home` use the code bellow:
   *
   * <code>
   * <?php
   *  foreach (sections() as $section) {
   *    
   *    // You can access to its fields just like a Page object
   *    $section->title();
   *    
   *    // Renders the section
   *    $section->render();
   *    
   *    // Sends custom vars to the template of a section
   *    $section->render(array(
   *      'foo' => 'value',
   *      'bar' => 'value 2',
   *      // ...
   *    ));
   *    
   *    // Stores the render result in a var
   *    $var = $section->render(array('foo' => 'bar'), true);
   *  }
   * ?>
   * </code>
   *
   * @param Page $parent The parent page
   * @author fenixkim
   */
  function __construct(Page $parent) {
    
    $this->parent = $parent;
    
    // Add to the collection each item as Section
    foreach(self::all($parent) as $page) {
      
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
    
  /**
   * Renders all sections
   *
   * Usage:
   *
   * <code>
   * <?php sections()->render(); ?>
   * </code>
   *
   * @return void
   * @author fenixkim
   */
  public function render() {
    foreach($this->data as $section) self::renderSection($section);
  }
  
  /**
   * Renders only the visible sections
   *
   * Usage:
   *
   * <code>
   * <?php sections()->renderVisible(); ?>
   * </code>
   *
   * @return void
   * @author fenixkim
   */
  public function renderVisible() {
    foreach($this->visible() as $section) {
      self::renderSection($section);
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
      return !str::startsWith($child->template(), '_');
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
  static public function renderSection(Section $section, $data = array(), $return = false) {
    
    $controllerData = array();
    $template = (string)$section->template();
    $file = kirby()->roots()->controllers() . DS . "$template.php";
    
    if(file_exists($file)) {
      
      // Gets the callback controller from the cache
      $callback = a::get(self::$_controllerCache, $template);
      
      // Or include once and cache it
      if (!$callback) {
        $callback = include_once($file);
        self::$_controllerCache[$template] = $callback;
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
  static public function all(Page $page) {
    return $page->children()->filter(function($child) {
      return self::pageIsSection($child);
      // return str::startsWith($child->template(), '_');
    });
  }
  
  /**
   * Count the Section objects in the parent Page
   *
   * @param Page $page The parent Page
   * @return int
   * @author fenixkim
   */
  static public function countSections(Page $page) {
    return self::all($page)->count();
  }
  
  /**
   * Check if a Page object has sections
   *
   * @param Page $page 
   * @return void
   * @author fenixkim
   */
  static public function hasSections(Page $page) {
    return self::countSections($page) > 0;
  }
  
  /**
   * Check if a Page is a Section
   *
   * @param Page $page 
   * @return bool
   * @author fenixkim
   */
  static public function pageIsSection(Page $page) {
    return str::startsWith($page->template(), '_');
  }
  
  /**
   * Avoid direct link for sections
   *
   * @param Page $page The Page of the section
   * @param string $redirectToParent If true, the page will be redirected the the parent page
   * @return void
   * @author fenixkim
   */
  static public function avoidDirectLink($page, $redirectToParent = true, $addHash = true) {
    
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
}

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
  public function render($data = array(), $return = false) {
    return Sections::renderSection($this, $data, $return);
  }
  
  /*
  // @TODO Add this method directly on the object
  public function avoidDirectLink($redirectToParent = true, $addHash = true) {
    Sections::avoidDirectLink($this);
  }
  */
}

/**
 * Sections helper
 *
 * @param Page $parent 
 * @return Collection A collection of Section objects
 * @author fenixkim
 */
function sections(Page $parent = null) {
  // Fetchs the current page if $parent is null
  return new Sections($parent ? $parent : page());
}