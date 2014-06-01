# Send Images to RSS

WordPress plugin that replaces smaller images with their full-size counterpart in RSS feeds. I like this for sending images from a WordPress gallery, for example--instead of sending thumbnails to the RSS readers, they get the full size images.

## Description

This plugin searches your RSS feed for any <img> tag and looks for evidence of a WordPress generated image (the filename ends in -150x150 for thumbnails, for example). It strips that from the image src so that the full size image is what's being sent to the RSS reader.

Then the plugin scans all images in the feed and removes height, sets width and max-width.

The plugin adds a new image size called "mailchimp" to WordPress. Any large images uploaded to your site with this plugin activated will automatically have a new copy generated which is 560 pixels wide. If this image exists, it will be sent to MailChimp, so we avoid the issue of overlarge images going out in email. (Images uploaded prior to activating this plugin will not be affected unless you regenerate thumbnails on your site.)

## Requirements
* WordPress 3.5, tested up to 4.0alpha

## Installation

### Upload

1. Download the latest tagged archive (choose the "zip" option).
2. Go to the __Plugins -> Add New__ screen and click the __Upload__ tab.
3. Upload the zipped archive directly.
4. Go to the Plugins screen and click __Activate__.

### Manual

1. Download the latest tagged archive (choose the "zip" option).
2. Unzip the archive.
3. Copy the folder to your `/wp-content/plugins/` directory.
4. Go to the Plugins screen and click __Activate__.

Check out the Codex for more information about [installing plugins manually](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

### Git

Using git, browse to your `/wp-content/plugins/` directory and clone this repository:

`git clone git@github.com:robincornett/send-images-rss.git`

Then go to your Plugins screen and click __Activate__.

## Frequently Asked Questions

### How can I change the size of the image being sent to the RSS?

At this time, you can't. If you upload large images, they'll be full width images in your email. With the new image size, however, they won't be excessively large, even if you upload excessively large images to your site.

### What about smaller images?

Smaller images will still be small. WordPress handles image alignment differently than email clients (set by class v. align). If your image has an alignment set in post, the plugin will assign an alignment in your email as well, and add a margin.

## Credits

* Built by [Robin Cornett](http://robincornett.com/)
* Inspired by [Chris Coyier, CSS-Tricks](http://css-tricks.com/dealing-content-images-email/)
* With insight from [David Gale](http://davidsgale.com)