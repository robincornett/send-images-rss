=== Send Images to RSS ===

Contributors: littler.chicken, garyj
Donate link: https://robincornett.com/donate/
Tags: email, RSS, images, feed, mailchimp, email campaign, RSS email, feedburner, email marketing, featured image RSS, excerpts
Requires at least: 3.8
Tested up to: 4.3
Stable tag: x.y.z
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Makes your RSS emails look more like your website by converting large images/galleries to an email friendly format. Works with any RSS email service.

== Description ==

WordPress plugin that replaces images with an email friendly size image in RSS feeds. I like this for sending images from a WordPress gallery, for example--instead of sending thumbnails to the RSS readers, they get the full size images. Also, even if you like to upload large images to your site, this plugin will hopefully prevent you from blowing up people's email accounts.

The plugin optionally adds a new email friendly image size to WordPress. Any large images uploaded to your site with this plugin activated will automatically have a new copy generated which is an email friendly size. If this image exists, it will be sent to your RSS feed, so we avoid the issue of overlarge images going out in email. (Images uploaded prior to activating this plugin will not be affected unless you regenerate thumbnails on your site. But seriously, I wouldn't bother regenerating thumbnails, because you won't be sending old posts out via an RSS email.)

Spanish tranlation offered by [Web Hosting Hub](http://www.webhostinghub.com/)

== Installation ==

1. Upload the entire `send-images-rss` folder to your `/wp-content/plugins` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Optionally, visit the Settings > Media page to change the default behavior of the plugin.

== Frequently Asked Questions ==

= How can I change the size of the image being sent to the RSS? =

Most users should not need to change this. The plugin is designed with a default image size of 560 pixels for the width of the new image. If, however, your RSS email template is more or less than 600 pixels wide, or you're using a template with a sidebar, you may need to change this setting. What number you choose is up to you.

**Note:** If you use a template with a sidebar, I strongly recommend that you opt to use the Alternate Feed for your emails, as your images will be too small to be attractive on services like Flipboard and Feedly.

= Does this plugin work with excerpts? =

**YES INDEEDY.** It's true, as of version x.y.z, _Send Images to RSS_ works with RSS feeds set to show excerpts/summaries! With this change, there's a new plugin settings page to handle the additional settings, which allow you to add the featured image to your excerpt, set its alignment, and set the target number of words for the excerpt. Major props to [Gretchen Louise](http://gretchenlouise.com/) for her suggestions and help with this one.

= What about smaller images? =

Smaller images will still be small. WordPress handles image alignment differently than email clients (set by class v. inline align). If your smaller image has an right/left alignment set in post, the plugin will copy that alignment in your email as well, and add a margin.

= I have funky characters in my RSS feed and emails. Why? =

Because you have funky characters and/or invalid markup in your posts. The plugin attempts to process your feed and encode wonky markup, but if your server doesn't have certain packages installed, the fallback is to process your feed as is, warts and all.

= I installed this plugin and the email that I sent out five minutes later still had giant images. =

The plugin only generates properly sized images for new uploads--anything you uploaded before the plugin was active will still be giant, if that's what you uploaded and your email client ignores a max-width setting. You can re-upload the images and they should behave as desired.

= What is this Alternate Feed? =

Because I use Feedly, and as a former photographer, it bothers me to see the freshly rendered email sized images blown up and soft to fit Feedly/Feedburner specs. So this gives you the option of having your main feed(s) with large images (galleries will be converted, too), but a special email-only feed which you can use with an email service like MailChimp, Campaign Monitor, or FeedBurner.

= I selected Alternate Feed, clicked the link for my new feed, and got a 404 (Page Not Found). Help? =

If this happens, your permalink for the new feed may not have been updated. Visit Settings > Permalinks in your admin. Save Changes if you like, and refresh your feed page.

= What is Simplify Feed? =

If you use native WordPress galleries in your posts, they're sent to your feed as thumbnails. Even if you do not use an RSS/email service, you can still use this plugin to sort out your galleries for subscribers who use an RSS reader. If you select Simplify Feed, your galleries will be converted, but there will not be an email sized image created, and no alternate feed will be created.

= I uploaded a large image to my post, but inserted a smaller version of it. The feed output a large version instead of the small. Can I change that? =

Yes, now you can change that. By default, the plugin simply looks to see if an email appropriate size image exists, and uses that, but this behavior will override small images in your posts if that large version exists. To make sure that the small image is used even if the large one exists, add this filter to your site, either in your functions.php file or a functionality plugin:

    add_filter( 'send_images_rss_change_small_images', '__return_false' );

= What if I upload my images to [flickr] or use images hosted somewhere other than my website? =

_Send Images to RSS_ works best with images uploaded through your WordPress website, because WordPress automatically creates the correct size images needed. Because there isn't really much we can do with images hosted elsewhere, the plugin ignores them by default. If, however, you want the plugin to at least _try_ to work with images hosted outside of your site (YMMV), you can add this filter to your site, either in your theme's functions.php file or a functionality plugin:

    add_filter( 'send_images_rss_process_external_images', '__return_true' );

= Is there a way to change the styling on the images in my feed? =

Yes, there sure is. To modify large/email size images, use a filter like this:

    add_filter( 'send_images_rss_email_image_style', 'rgc_email_images', 10, 2 );
    function rgc_email_images( $style, $maxwidth ) {
        $style = sprintf( 'display:block;margin:10px auto;max-width:%spx;', $maxwidth );

        return $style;
    }

You can also filter styling for images with captions, or images which do not have an email size version generated for some reason. I would look into `/includes/class-sendimagesrss-feed-fixer.php` to really examine the filters, but here's a quick example for the images:

    add_filter( 'send_images_rss_other_image_style', 'rgc_change_other_images', 10, 6 );
    function rgc_change_other_images( $style, $width, $maxwidth, $halfwidth, $alignright, $alignleft ) {

        $style = sprintf( 'display:block;margin:10px auto;max-width:%spx;', $maxwidth );

        if ( $width < $maxwidth ) {
            $style = sprintf( 'maxwidth:%spx;', $halfwidth );
        }

        return $style;
    }

The filter for captions is `send_images_rss_caption_style`, but takes the same arguments as above.

== Screenshots ==

1. Screenshot of the optional plugin settings in Settings > Send Images to RSS.

== Upgrade Notice ==

= x.y.z =
Huge update: now includes featured image support for excerpt only feeds.

== Changelog ==

= x.y.z =
* new: optionally add your featured image to the excerpt in your feed!
* new: settings page has been added to handle excerpt settings.

= 2.6.1 =
* bugfix: correctly handles with captions wrapped around a linked image.
* bugfix: activation error for some users FIXED.

= 2.6.0 =
* added a filter to optionally attempt to process external images.
* added a filter to optionally not replace small images in post content.
* added filters for granular control over image/caption styling.
* bugfix: if images are external, they no longer completely stop the presses.

= 2.5.2 =
* added filter to process images correctly if user has Photon (Jetpack) enabled
* added Spanish translation, provided by [Web Hosting Hub](http://www.webhostinghub.com/)
* added error message for users who have their feed set to Summary instead of Full text
* changed error messages to be less invasive
* fixed feed output if user has older PHP (pre 5.3.6)

= 2.5.1 =
* bugfix: if images are external, they are not processed by the plugin
* content is loaded more efficiently
* encoding tweaks
* escaped things

= 2.5.0 =
* added new function to deal with captions and alignment.
* deprecated original caption function since we have a whole new wonderland of caption action.
* refactoring due to a lot more things being processed.
* also set small image width to be max of 1/2 the max-width set in Media Settings.

= 2.4.2 =
* updated for new WordPress version.
* added plugin icon.
* moved, but did not change, main image function.

= 2.4.1 =
* bugfix: corrected sanitization method for email image size.

= 2.4.0 =
* Added a simplify feed method, which allows user to clean up galleries only, without creating an email friendly feed.
* Many much refactoring and input and tail kicking from the incomparable [Gary Jones](http://gamajo.com)
* Help tab added to media settings page.

= 2.3.0 =
* Added an alternate feed method so that original feed could serve up full sized images while alternate feed would be used for email.

= 2.2.0 =
* Added a image width setting to the Settings > Media screen so that the MailChimp size image can be changed.

= 2.1.1 =
* Revised for OOP
* integrated gallery scan into main function

= 2.1.0 =
* Much revising--set conditional to use MailChimp size image if exists
* Updated function to retrieve image URL to not use guid
* Changed filter to the_content instead of the_content_rss due to shortcode explosions
* If an image is smaller than MailChimp size, left/right alignment will be honored; otherwise, alignment will be set to center.
* If a post has a gallery, an additional scan occurs to pull full size images and use those (if a MailChimp size image exists, it will still be used).

= 2.0.0beta =
* Total rewrite
* Adds a new image size called 'mailchimp' to WordPress so that it can be used instead of trying to shoehorn the large images.
* strips out GravityForm shortcodes, but if others exist, that could be problematic.

= 1.1.1 =
* simplified immensely. Dropped need for user to edit plugin files.
* deals with captions.

= 1.0.0 =
* Initial release.