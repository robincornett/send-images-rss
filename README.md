# Send Images to RSS

WordPress plugin that replaces smaller images with their full-size counterpart in RSS feeds. I like this for sending images from a WordPress gallery, for example--instead of sending thumbnails to the RSS readers, they get the full size images.

## Description

This plugin searches your RSS feed for any <img> tag and looks for evidence of a WordPress generated image (the filename ends in -150x150 for thumbnails, for example). It strips that from the image src so that the full size image is what's being sent to the RSS reader. [source](http://kb.mailchimp.com/article/why-does-my-email-look-like-monkey-poop-in-outlook/)

Caveat: if you're using a service like MailChimp (woot!) for your RSS emails, they do warn that email clients can ignore the width set by the plugin, and instead just send the full size image. Don't know a way to work around that one yet.

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

`git clone git@github.com:robincornett/simple-listings-genesis.git`

Then go to your Plugins screen and click __Activate__.

## Frequently Asked Questions

### How can I change the size of the image being sent to the RSS?

On line 29 of send-images-rss.php, change the width to something other than 560 (I chose this size based on the default email template from MailChimp). You can try 100%, for example, or if you want your images to be smaller in your emails, do 250. If you comment out line 29 and uncomment lines 30 and 31, your images should be all 250 pixels wide and aligned right.

###
## Credits

Built by [Robin Cornett](http://www.robincornett.com/)