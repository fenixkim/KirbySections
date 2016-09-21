<?php

use Kirby\Sections\Sections as Sections;

// Autoloader
load(array(
	'kirby\\sections\\sections'    => __DIR__ . DS . 'lib' . DS . 'sections.php',
  'kirby\\sections\\section'     => __DIR__ . DS . 'lib' . DS . 'section.php',
));

/**
 * Sections helper
 *
 * @param Page $parent 
 * @return Collection A collection of Section objects
 * @author fenixkim
 */
function sections(Page $parent = null) {
  
  // Fetchs the current page if $parent is null
  return new Sections($parent ?: page());
}

$kirby->set('page::method', 'sections', function($page) {
	return new Sections($page ?: page());
});

$kirby->set('page::method', 'isSection', function($page) {
	return Sections::pageIsSection($page);
});

$kirby->set('page::method', 'countSections', function($page) {
	return $page->sections()->count();
});

$kirby->set('page::method', 'hasSections', function($page) {
	return $page->sections()->count() > 0;
});

$kirby->set('page::method', 'avoidDirectLink', function($page, $redirectToParent = true, $addHash = true) {
	Sections::avoidDirectLink($page, $redirectToParent, $addHash);
  return $page;
});

$kirby->set('pages::method', 'notSections', function($pages) {
  return $pages->filter(function($child) {
    return !str::startsWith($child->template(), Sections::prefix());
  });
});