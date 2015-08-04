# Sections Plugin for KirbyCMS

Sections are like habitual Page objects but they can be rendered into another templates just like the `snippets` but allowing add its own templates, blueprints, controllers and models.

Currently there is an official solution to get something similar using `snippents` at <http://getkirby.com/docs/solutions/one-pager> but that tecnique don't allows a friendly integration with the Kirby's Panel.

Sections are differents of Pages because the name of its templates begin with '_', ie: `_hero-header.php` and you could render a Section into a template of a Page as many times as you want.

## Installation

1. Download [Sections Plugin](https://github.com/fenixkim/KirbySections/zipball/master).
2. Copy the downloaded folder in `site/plugins` of your site as `sections/`

## Example usage in Kirbytext

Add your sections in the same way as you add a subpage in `site/template` and in the `content` folder but the name of the files should begin with an underscore "_" in order to difference this of the normal pages. Example:

### Templates & Blueprints

content/home                | site/templates    | site/blueprints
:-------------------------- | :---------------- | :---------------
01-hero/_hero-header.txt    | _hero-header.php  | _hero-header.php
02-services/_services.txt   | _services.php     | _services.php
03-teamwork/_team.txt       | _team.php         | _team.php
03-testominials/_quotes.txt | _quotes.php       | _quotes.php
**Invisible Sections**      |                   | 
newsletter/_newsletter.txt  | _newsletter.php   | _newsletter.php
promotions/_promo.txt       | _promo.php        | _promo.php

The names of the pages, template and blueprints must match.

### Controllers & Models
	  
If you prefer, you also could add [controllers](http://getkirby.com/docs/templates/controllers) & [models](http://getkirby.com/docs/templates/models).

**Important:** If you want to use Models, the name of de Sections can't contain dashes (`-`) and the should be a subclass of `Section`. 

Ie: Rename `_hero-header.php` to `_heroheader.php` and create its model as follow.

```php
class HeroHeaderPage extends Section {
  public function method() {
    // ... return something here
  }
}
```

### Fetch and render the sections

Render the sections in a template file of a Page, ie: home.php

```html
<? snippet('header') ?>
  <?php sections()->render() ?>
<? snippet('footer') ?>
```	

Or only the visible sections

```php
sections()->renderVisible();
```
	
Or render in a loop

```php
foreach (sections() as $section) {
   $section->render();
}
```

Firter de visibles in a loop

```php
foreach (sections()->visible() as $section) {
   $section->render();
}
```

Send variables to the template of the section

```php
foreach (sections() as $section) {
   $section->render(array(
     'foo' => 'value1',
     'bar' => 'value2',
     // ...
   ));
}
```

If you prefer you could fetch the sections of a specific page

```php
sections(page('uri'))->render();
```

## Avoid direct links

As sections are fragments of code that are rendered in a template, they don't shoud have a direct link access so you need to edit the template of a section as follow:

```html+php
<!-- testimonials -->
<?php Sections::avoidDirectLink($page); ?>

<section>
  <h1><?php echo $page->title() ?></h1>
  <?php echo $page->content()->kirbytext() ?>
  ...
</section>
```

The section will be redirected to the parent page, in this case, when the user tries to visit `http://site.com/home/testominials` the site will be redirected to `http://site.com/#testominials`. A hash `#...` will be added to the url. so in order to get a better aproach of this, you may add an id to your `<section>` tag as follow:

```html+php
<?php Sections::avoidDirectLink($page); ?>

<section id="<?php echo $page->uid() ?>">
  <h1><?php echo $page->title() ?></h1>
  <?php echo $page->content()->kirbytext() ?>
  ...
</section>
```

If you want to disable the hash, you could set false to the tirth argument:

```php
<?php Sections::avoidDirectLink($page, true, false); ?>
```
	
If you don't want to redirect, just sent a false to the second argument and a error page will be showed instead

```php
<?php Sections::avoidDirectLink($page, false); ?>
```

## Other useful methods

```php	
// Gets all the Section objects from a Page object
Sections::all($page);

// Count the Section objects in the parent Page
Sections::countSections($page);

// Check if a Page is a Section
Sections::pageIsSection($page);

// Check if a Page object has sections
Sections::hasSections($page);
```

The `Sections::hasSections()` method is usefull to avoid the printing of sections in menus or sitemaps. You could try something like this:

```html+php
<? if ($page->hasVisibleChildren() && !Sections::hasSections($page)): ?>
  <a href="<?php echo $page->url() ?>"><?php echo html($page->title()) ?></a>
<? endif ?>
```
