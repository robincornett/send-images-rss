# Send Images to RSS

WordPress plugin that replaces images with an email friendly size image in RSS feeds. I like this for sending images from a WordPress gallery, for example--instead of sending thumbnails to the RSS readers, they get the full size images. Also, if you like to upload large images to your site, this plugin will hopefully prevent you from blowing up people's email accounts.

## Description

The plugin adds a new image size called "mailchimp" to WordPress. Any large images uploaded to your site with this plugin activated will automatically have a new copy generated which is 560 pixels wide. If this image exists, it will be sent to MailChimp, so we avoid the issue of overlarge images going out in email. (Images uploaded prior to activating this plugin will not be affected unless you regenerate thumbnails on your site. But seriously, I wouldn't bother regenerating thumbnails, because you won't be sending old posts out via an RSS email.)

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

### I have funky characters in my RSS feed and emails. Why?

Because you have funky characters and/or invalid markup in your posts. The plugin attempts to process your feed and encode wonky markup, but if your server doesn't have certain packages installed, the fallback is to process your feed as is, warts and all.

## Credits

* Built by [Robin Cornett](http://robincornett.com/)
* Inspired by [Erik Teichmann](http://www.eriktdesign.com/) and [Chris Coyier, CSS-Tricks](http://css-tricks.com/dealing-content-images-email/)
* With insight from [David Gale](http://davidsgale.com) and [Gary Jones](http://gamajo.com)