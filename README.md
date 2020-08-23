# Send Images to RSS

_Send Images to RSS_ bridges the gap between large websites and small emails, by replacing images in your feed with smaller, email friendly images, and attempting to add markup which email clients can handle.

Between larger monitors, retina screens, and better image optimization, the images we serve up on our websites are larger than they've ever been. RSS to email services such as MailChimp, however, are constrained to what email clients can display, which is small, small, small. And although it's possible to try to style images with a max-width in your emails, not all clients will honor it (I'm looking at you, Outlook).

_Send Images to RSS_ makes it easy to create beautiful, email friendly RSS campaigns, with minimal setup required, regardless of your feed setup.

### Full Text RSS Feeds

If your site's RSS feed is set to Full Text, this plugin makes sure your emails look more like your website:

* Replace overly large images with email friendly size images.
* Convert galleries from thumbnails to full width images.
* Add email friendly styling/alignment to your images.

### Summary Text Feeds

If you've used Summaries as your RSS feed settings, this plugin has not been for you. _Until now._ As of 3.0.0, Send Images to RSS brings the awesome to you, too. Here's the magic for your Summary feed:

* Add the post's featured image to your excerpt. Choose the size and alignment. If no featured image is set, the plugin will use the first image uploaded to the post.
* Set a custom length for your RSS summary/excerpt. Pick the number of words you want your summary to have, and the plugin will aim for that, but with the added bonus of making sure the final sentence is complete.
* If you add a manual excerpt to your post, because you like to have full control, the plugin will properly use that instead.
* Automatically add a custom "read more" link to the end of every post summary, to keep your feed pointed back to your site.

### Known (non)Issues

This plugin should work with any theme. Some themes and plugins do modify the feed for their own purposes. Where possible, I've tried to account for them:

* For summary feeds, the _Yoast SEO_ RSS link is removed (the full text feed and front end output are not changed).
* For summary feeds, the excerpt filter added by the _Woo Canvas_ theme is removed (the full text feed and front end output are not changed).
* For summary feeds, this plugin will replace the image settings for _Display Featured Image for Genesis_ for versions 2.3.0 and later (because this plugin is smarter). If you're using _Display Featured Image for Genesis_ 2.2.2 or lower, this plugin will concede graciously. But you should update, please.
* For full text feeds, this plugin will not duplicate featured images if they are being added by _Display Featured Image for Genesis_--you will want to disable that feature in _Display Featured Image for Genesis_.

**NOTE: it is up to you to check that your feed output is still working, especially in your email system of choice, once it's installed.** I've attempted to set it up to handle XHTML or HTML5, and function even if your feed is wonky, but **please** double check, and let me know if you have issues, and if so, what specifically they are.

### Props

Special thanks to [Gretchen Louise](http://gretchenlouise.com/) for her summary feed contributions.

Spanish translation offered by [Web Hosting Hub](http://www.webhostinghub.com/)

## Requirements
* WordPress 4.9, tested up to 5.5

## Installation

### Upload

1. Download the latest tagged archive (choose the "zip" option).
2. Go to the __Plugins -> Add New__ screen and click the __Upload__ tab.
3. Upload the zipped archive directly.
4. Go to the Plugins screen and click __Activate__.
5. Visit the Settings > Send Images to RSS page to change the default behavior of the plugin.

### Manual

1. Download the latest tagged archive (choose the "zip" option).
2. Unzip the archive.
3. Copy the folder to your `/wp-content/plugins/` directory.
4. Go to the Plugins screen and click __Activate__.
5. Visit the Settings > Send Images to RSS page to change the default behavior of the plugin.

Check out the Codex for more information about [installing plugins manually](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

### Git

Using git, browse to your `/wp-content/plugins/` directory and clone this repository:

`git clone git@github.com:robincornett/send-images-rss.git`

Then go to your Plugins screen and click __Activate__.

## Frequently Asked Questions

### Note for [new] MailChimp users:

MailChimp has added a new setting to the RSS campaign setup process: "Resize RSS feed images to fit template" ... please __do not__ use this setting, especially if you are using excerpts with small images, or using small images with alignment, because MailChimp will blow them up and make them sad and ugly.

### How can I change the size of the image being sent to the RSS?

Most users should not need to change this. The plugin is designed with a default image size of 560 pixels for the width of the new image. If, however, your RSS email template is more or less than 600 pixels wide, or you're using a template with a sidebar, you may need to change this setting. What number you choose is up to you.

Mad Mimi users should set this to 530.

__Note:__ If you use an email template with a sidebar, I strongly recommend that you opt to use the Alternate Feed for your emails, as your images will be too small to be attractive on services like Flipboard and Feedly.

### What about featured images?

If your site's feed settings are set to Summary instead of Full Text, the featured image (or first image) will be added to each post. As of version 3.1.0, you can now add your post's featured image to the full text feed as well. If you use this setting, please double check your feed (again) to make sure you don't have duplicate featured images, as some themes and plugins do this as well. (If you are a _Display Featured Image for Genesis_ user, I've got you covered--this setting will not work until you've deactivated this setting in that plugin.)

If you have added the featured image to your feed excerpt using your own functions, or another plugin, you will need to get rid of that before using this plugin, or select "No Image" for the Featured Image Size.

### Does this plugin work with excerpts?

**YES INDEEDY.** It's true, as of version 3.0.0, _Send Images to RSS_ works with RSS feeds set to show excerpts/summaries! With this change, there's a new plugin settings page to handle the additional settings, which allow you to add the featured image to your excerpt, set its alignment, and set the target number of words for the excerpt. If a post has images uploaded to it (attached), but no featured image, the plugin will use the first attached image for the excerpt.

### What about smaller images?

Smaller images will still be small. WordPress handles image alignment differently than email clients (set by class v. inline align). If your smaller image has an right/left alignment set in post, the plugin will copy that alignment in your email as well, and add a margin.

### I uploaded a large image to my post, but inserted a smaller version of it. The feed output a large version instead of the small. Can I change that?

Yes, you can change that. By default, the plugin simply looks to see if an email appropriate size image exists, and uses that, but this behavior will override small images in your posts if that large version exists. To make sure that the small image is used even if the large one exists, disable the "Change Small Images" setting in the plugin's settings page.

Note to iThemes Security users: if you are using the HackRepair.com blacklist feature, you will not be able to make use of this filter, because it blocks how _Send Images to RSS_ retrieves image data in the feed. I would **not** suggest disabling the security feature just to be able to use this filter.

### I have funky characters in my RSS feed and emails. Why?

Because you have funky characters and/or invalid markup in your posts. The plugin attempts to process your feed and encode wonky markup, but if your server doesn't have certain packages installed, the fallback is to process your feed as is, warts and all.

### I installed this plugin and the email that I sent out five minutes later still had giant images.

The plugin only generates properly sized images for new uploads--anything you uploaded before the plugin was active will still be giant, if that's what you uploaded and your email client ignores a max-width setting. You can re-upload the images and they should behave as desired.

### What is this Alternate Feed?

Because I use Feedly, and as a former photographer, it bothers me to see the freshly rendered email sized images blown up and soft to fit Feedly/Feedburner specs. So this gives you the option of having your main feed(s) with large images (galleries will be converted, too), but a special email-only feed which you can use with an email service like MailChimp, Campaign Monitor, or FeedBurner.

### I selected Alternate Feed, clicked the link for my new feed, and got a 404 (Page Not Found). Help?

If this happens, your permalink for the new feed may not have been updated. Visit Settings > Permalinks in your admin. Save Changes if you like, and refresh your feed page.

### What is Simplify Feed?

If you use native WordPress galleries in your posts, they're sent to your feed as thumbnails. Even if you do not use an RSS/email service, you can still use this plugin to sort out your galleries for subscribers who use an RSS reader. If you select Simplify Feed, your galleries will be converted, but there will not be an email sized image created, and no alternate feed will be created.

### What if I upload my images to [flickr] or use images hosted somewhere other than my website?

_Send Images to RSS_ works best with images uploaded through your WordPress website, because WordPress automatically creates the correct size images needed. However, the plugin will add inline styling to all images to attempt to make them fit your email template.

### Is there a way to change the styling on the images in my feed?

Yes, there sure is. To modify large/email size images, use a filter like this:

```php
add_filter( 'send_images_rss_email_image_style', 'rgc_email_images', 10, 2 );
function rgc_email_images( $style, $maxwidth ) {
	return sprintf( 'display:block;margin:10px auto;max-width:%spx;', $maxwidth );
}
```

You can also filter styling for images with captions, or images which do not have an email size version generated for some reason. I would look into `/includes/class-sendimagesrss-feed-fixer.php` to really examine the filters, but here's a quick example for the images:

```php
add_filter( 'send_images_rss_other_image_style', 'rgc_change_other_images', 10, 6 );
function rgc_change_other_images( $style, $width, $maxwidth, $halfwidth, $alignright, $alignleft ) {

	$style = sprintf( 'display:block;margin:10px auto;max-width:%spx;', $maxwidth );

	if ( $width < $maxwidth ) {
		$style = sprintf( 'maxwidth:%spx;', $halfwidth );
	}

	return $style;
}
```

The filter for captions is `send_images_rss_caption_style`, but takes the same arguments as above.

### What if I want the full feed to be processed AND have the featured image added to the excerpt?

As of version 3.1.1, you can do this on the plugin settings page. Make sure your feed settings are set to **full text**, and then check the "Process Both Feeds" setting.

If you used the filter to set this up in earlier versions of the plugin, you can leave the filter in place, and it will continue to work, or you can remove it and use this setting instead. The filter will always override the option on the settings page.

## Screenshots
![Screenshot of the plugin settings in Settings > Send Images to RSS.](https://github.com/robincornett/send-images-rss/blob/develop/assets/screenshot-1.png)  
__Screenshot of the plugin settings in Settings > Send Images to RSS.__

## Credits

* Built by [Robin Cornett](https://robincornett.com/)
* With major insight [Gary Jones](https://garyjones.io/)
* Inspired by [Erik Teichmann](https://www.eriktdesign.com/) and [Chris Coyier, CSS-Tricks](http://css-tricks.com/dealing-content-images-email/)
* Thanks to [Gretchen Louise](https://gretchenlouise.com/) for her suggestions and help on the new excerpt options

## Changelog

### 3.4.0
* added: support for core gallery block
* updated: new minimum WordPress version 4.9
* updated: new minimum PHP version 5.6.20
* fixed: image IDs not being properly retrieved if the original image is larger than 2560 px wide

### 3.3.2
* fixed: error with external images/captions on full feeds

### 3.3.1
* fixed: overzealous tag stripping on excerpts (props @gretchenlouise for reporting)

### 3.3.0
* added: filter for the excerpt permalink
* added: filter to allow users to keep shortcodes in excerpts
* added: support for <video> embeds in full text feeds
* changed: additional max-width set on images to help prevent overflow (props @ivanpr)
* fixed: margins on featured images set to align: none
* fixed: featured images added to full content no longer remove iframes

### 3.2.2
* changed: link back to site from excerpt is now nofollow, but can be overridden via filter
* changed: added margin to excerpt style filter parameters

### 3.2.1
* fixed: default settings key added for processing both feeds

### 3.2.0
* added: setting to not change small images in content, even if a larger version exists
* added: filter to change the RSS thumbnail size
* updated: now allows an alternate feed, even if it's summaries only
* updated: reversed decision from 2.5 to ignore external images; back to doing what we can to make them fit
* fixed: image ID properly returns as false if there is a URL mismatch
* fixed: settings page is now accessible
* fixed: centering images (smaller than email width)

### 3.1.1
* added: option to process both the full text and summary feeds simultaneously.

### 3.1.0
* added: now include the featured image with your full text feeds
* fixed: disable responsive images in RSS feeds (sorry, WP 4.4, but this isn't helpful here)
* fixed: max-width style on featured images

### 3.0.1
* improved: moved filters to individual functions
* bugfix: fixed image ID retrieval if protocol mismatch (eg secure admin, nonsecure front end)
* bugfix: override Photon in every possible way
* bugfix: added missing permalink to excerpt read more filter

### 3.0.0
* new: optionally add your featured image to the excerpt in your feed!
* new: settings page has been added to handle excerpt settings.
* improved: the full text feed should now parse more quickly.
* improved: plugin settings are now saved to the database as an array.
* bugfix: fixed conflict with iThemes security

### 2.6.1
* bugfix: correctly handles with captions wrapped around a linked image.
* bugfix: activation error for some users FIXED.

### 2.6.0
* added a filter to optionally attempt to process external images.
* added a filter to optionally not replace small images in post content.
* added filters for granular control over image/caption styling.
* bugfix: if images are external, they no longer completely stop the presses.

### 2.5.2
* added filter to process images correctly if user has Photon (Jetpack) enabled
* added Spanish translation, provided by [Web Hosting Hub](http://www.webhostinghub.com/)
* added error message for users who have their feed set to Summary instead of Full text
* changed error messages to be less invasive
* fixed feed output if user has older PHP (pre 5.3.6)

### 2.5.1
* bugfix: if images are external, they are not processed by the plugin
* content is loaded more efficiently
* encoding tweaks
* escaped things

### 2.5.0
* added new function to deal with captions and alignment.
* deprecated original caption function since we have a whole new wonderland of caption action.
* refactoring due to a lot more things being processed.
* also set small image width to be max of 1/2 the max-width set in Media Settings. Less arbitrary than 280px.

### 2.4.2
* updated for new WordPress version.
* added plugin icon.
* moved, but did not change, main image function.

### 2.4.1
* Sanitization bug fix.

### 2.4.0
* Added a simplify feed method, which allows user to clean up galleries only, without creating an email friendly feed.
* Many much refactoring and input and tail kicking from the incomparable [Gary Jones](http://gamajo.com)
* Help tab added to media settings page.

### 2.3.0
* Added an alternate feed method so that original feed could serve up full sized images while alternate feed would be used for email.

### 2.2.0
* Added a image width setting to the Settings > Media screen so that the MailChimp size image can be changed.

### 2.1.1
* Revised for class
* integrated gallery scan into main function

### 2.1.0
* Much revising--set conditional to use MailChimp size image if exists
* Updated function to retrieve image URL to not use guid
* Changed filter to the_content instead of the_content_rss due to shortcode explosions
* If an image is smaller than MailChimp size, left/right alignment will be honored; otherwise, alignment will be set to center.
* If a post has a gallery, an additional scan occurs to pull full size images and use those (if a MailChimp size image exists, it will still be used).

### 2.0.0beta
* Total rewrite
* Adds a new image size called 'mailchimp' to WordPress so that it can be used instead of trying to shoehorn the large images.
* strips out GravityForm shortcodes, but if others exist, that could be problematic.

### 1.1.1
* simplified immensely. Dropped need for user to edit plugin files.
* deals with captions.
