## Shortcodes
 - - -

### Button ###

>  Displays a branded call to action button. [See some examples](http://uamsonlinedev.com/uams-button-examples/). 

> Attributes:  

> - __color__ : The color of the button. Options: __red__, __gray__, __blue__, or __green__ (_Default: none_)
> - __type__: Adjusts the image of the button. Options: __plus__, __go__, __external__, __play__, __pdf__ (_Default: go_)   
> - __size__: Adjusts the size of the button. Options: __small__, __large__ (_Default: None_)
> - __url__: The URL where the button links to (_Default: None_)

> Examples:   
```
  [button color=red url="http://uams.edu"]Button Text[/button]
  
  [button color="red" type=plus size="small" url="http://uams.edu"]Button Text[/button]
```

### Blogroll ###

>  This is a shortcode that wraps the WordPress [get\_posts](https://codex.wordpress.org/Template_Tags/get_posts) function and templates out a blogroll. Any parameter you can pass to `get_posts` will be understood along with the following. 

> Attributes:  

> -  __excerpt__ : Choose whether to show the excerpt in the blogroll. Options: __show__, __hide__. (_Default: hide_)
> - __trim__ : Whether or not to trim the words via WordPress [wp\_trim\_words](https://codex.wordpress.org/Function_Reference/wp_trim_words) function. Options: __true__, __false__. (_Default: false_)
> - __image__:  Choose whether to show the featured image thumbnail. Options: __show__, __hide__. (_Default: hide_) _\[Ignored by card style\]_
> - __author__: Choose whether to show the author. Options: __show__, __hide__. (_Default: show_)
> - __date__:  Choose whether to show the publish date. Options: __show__, __hide__. (_Default: show_)
> - __titletag__:  The html element for the post titles. (_Default: h2_)
> - __post\_type__:  The post type to look for.(_Default: post_)
> - __number__:  The maximum number of results to return (_Default: 5_)
> - ~~__mini__:  Use the miniture template instead of the default one. (_Default: false_)~~ **_depreciated, use style_**
> - __style__:  Use the template style. Options: __default__, __card__, __mini__ (_Default: default_)
> - __category__:  The WordPress category ID to limit the results from. (_Default: None_)
> - __category\_name__:  The WordPress category name to limit the results from. (_Default: None_)

> Example:
```
  [blogroll number=3 trim=true style=card]
```

### iFrame ###

>  Embed iframes into your post or page content without adjusting WordPress privileges.  
> The iframe url is tested against a list of allowed domains. If the domain is not in the list the iframe will not render.  

> Attributes:  

> - __src__ : The source URL of the embedded iframe (_Default: none_)
> - __height__ : The width of the embedded iframe (_Default: WordPress's embed size width setting_)
> - __width__ :  The height of the embedded iframe (_Default: WordPress's embed size height setting_)

> Example:   
```
    [iframe src="https://www.youtube.com/embed/Bmb270rrRqQ" height="500" width="700"]
```

> Allowed domains:
```
    uams.edu,
    uamshealth.com,
    google.com,
    youtube.com,
    pgcalc.com,
    docs.google.com,
    vimeo.com,
    facebook.com,
```


### Intro ###
> This shortcode creates an italicized block of introduction text for the content.

> No attributes.

> Example:

```
  [intro] A block on introductory text for the content. [/intro]
```

### Map ###
> This shortcode embeds a map into the body content.

> Attributes: 

> - __building__ : The building [id\_number](https://maps.uams.edu/map-ids/) (_Default: None_)
> - __width__ : The width of the embedded iframe (_Default: 100%_)
> - __height__ :  The height of the embedded iframe (_Default: 480px_) 

> Example:

```
  [map building='135' width='100%' height='480'][/map]
```

### RSS ###

>  This is a shortcode embeds an RSS blogroll into the body content. It behaves similarly to the UAMS RSS Widget.

> Attributes:  

> -  __url__ : The URL to parse for the RSS feed. (_Default: None_)
> - __number__:  The maximum number of results to return (_Default: 5_)
> - __title__:  The title for the RSS blogroll in the content (_Default: None_)
> - __heading__:  The html element for the post titles. (_Default: h3_)
> - __show_image__:  Choose whether to show the RSS thumbnail. Options: __true__, __false__. (_Default: true_)
> - __show_date__:  Choose whether to show the publish date. Options: __true__, __false__. (_Default: true_)
> - __show_more__: Choose whether to show the author. Options: __true__, __false__. (_Default: true_)

> Example:
```
  [rss url="http://www.washington.edu/marketing/topic/wordpress/feed" number=3 title="Web Team Updates" heading="h2" show_image="false" show_date="false"]
```

### Tile box ###

>  Display branded tiles to structure content in elegantly. [See an example of tiles here](http://#/).  
Each tile is setup as a series of shortcodes wrapped in `[box]` shortcode. 

> Attributes:  

> - __alignment__ : How the text is aligned in each tile. Options: __centered__ or __none__ (_Default: none_)
> - __color__ : Background color of the tiles. Options: __tan__ (_Default: none_)

> Example:   
```
[box alignment=centered]
    [tile] Text for tile one [/tile]
    [tile] Text for tile two [/tile]
    [tile] Text for tile three [/tile]
    [tile] Text for tile four [/tile]
  [/box]
```

### YouTube ###

>  Embed a YouTube video or playlist into your post content.

> Attributes:  

> -  __type__ : Pick whether to display a single video or playlist. Options: __single__, __playlist__. (_Default: None_)   
> - __id__ : The youtube video or playlist id  (_Default: None_)  
> - __max-results__ (__OPTIONAL__):  The maximum number of results to return for a playlist (_Default: None_)

> Examples: 

> __Single:__
```
  [youtube type='single' id='6sjcltWIlkQ']
``` 

> __Playlist:__
```
  [youtube type='playlist' id='PLgNkGpnjFWo9CN_HeVtujhMnUXK05iwgZ' max-results='10']
```

> __API key:__ The shortcode's script asks the YouTube Data API for the video or playlist details, and the request needs an API key. The theme does not carry a key. Define it in the site's `wp-config.php`, above the line that says to stop editing:

```
  define( 'UAMS_YOUTUBE_API_KEY', 'the-key-from-google-cloud' );
```

> The theme passes the value to the browser as `uams_youtube.apiKey`, so it is a __browser key__ that every visitor can read, and it has to be restricted in Google Cloud:
>
> - __API restrictions:__ allow only the YouTube Data API v3.
> - __Application restrictions:__ HTTP referrers, listing the site's domains.
>
> When the constant is not defined or is empty, the theme sends no request to YouTube and the shortcode's placeholder stays empty. To rotate the key, change the constant; no theme release is needed.

### Subpage List ###

> This shortcode lists out all the subpages relative to the current page. 
> There are two views this shortcode can render: list or grid. 
> The list view displays all the subpages as anchor tags in an HTML list element.
> The grid view displays all the subpages as boxes, with their title, excerpt and author if available.

> Attributes: 

> - __link__ : The text in the anchor tag that will link to the subpage (_Default: Read more_)
> - __tilebox__ : Enable the grid layout of the subpages ( _Default: false_ )


> Example: 
```
 [subpage-list link="More information here" tilebox=true ]
```

### Accordion ###
> This is an accessible version of the accordion menu based off of Nicolas Hoffmann's [accessible jQuery accordion](http://a11y.nicolas-hoffmann.net/accordion/).

> Attributes: 

> - __active__ : Open the accordion section by default (_Default: none_)
> 
> Example:   
```
  [accordion name='Accessible Accordion']
    [section title='Example'] Section[/section]
    [section title='Example' active='true'] Section[/section]
    [section title='Example'] Section[/section]
   [/accordion]
```

### Tabs ###
> This is an accessible version of the accordion menu based off of Nicolas Hoffmann's [Accessible tab panel using ARIA](https://van11y.net/accessible-tab-panel/)
>The title is what appears in each tab across the top. The link is optional.  The link is the text that is used in the hashtag (Ex. #linkname1). The Tab Text is the content for each tab.
> 
> Example:   
```
  [tabs name='Accessible Tabs']
    [tab title='Example'] Tab Text [/tab]
    [tab title='Example'] Tab Text [/tab]
    [tab title='Example'] Tab Text [/tab]
   [/tabs]
```

> Example with custom link names:   
```
  [tabs name='Accessible Tabs']
    [tab title='Example' link='linkname1'] Tab Text [/tab]
    [tab title='Example' link='linkname2'] Tab Text [/tab]
    [tab title='Example' link='linkname3'] Tab Text [/tab]
   [/tabs]
```

<!-- ### Custom Menu ###
> This shortcode pulls in a custom menu that can be created under _Dashboard > Appearance > Menus_. Icons can be added in the class field in the menu builder. View the [full set of icons](http://www.#.edu/brand/web-2/html-web-components/web-icons/) for more information.
> 
> Example:   
```
  [custommenu menu=Menu-name-here]
```
> Attributes:  

> -  __menu__ : Enter the name of the menu found in _Dashboard > Appearance > Menus_. (_Default: Main menu_)   --> 




