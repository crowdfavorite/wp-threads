=== Plugin Name ===
Contributors: alexkingorg, crowdfavorite
Tags: content, timeline, display, presentation, story, storyline, context
Requires at least: 3.5
Tested up to: 3.5.1
Stable tag: 1.0b1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Threads displays a timeline of related posts.

== Description ==

If you have ongoing themes you write about on your site, you can use Threads to show those posts in a timeline, with a link to the timeline from each of the posts. This helps you avoid feeling like you have to rehash too much history about the topic in each post.

Another good usage of Threads is on a news site to track posts related to a single ongoing story. For example, a tech blog might create a thread to group stories about a product launch event. Several months later, stories about sales figures for the product might be added to the thread. By placing all of these posts in a thread, there is a useful visual way of browsing all of the posts.

The timeline display of a thread is both responsive and retina (HiDPI) friendly. See an <a href="https://alexking.org/blog/thread/content">example here</a>.

Developers, please contribute on <a href="https://github.com/crowdfavorite/wp-threads">GitHub</a>.

== Installation ==

1. Upload the `threads` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Optional: add the Recent Threads widget to your sidebar
1. Optional: use the shortcode to display a thread timeline in a post or page

Shortcode syntax:

`[thread term="thread-slug"]`

== Frequently Asked Questions ==

= What is the time threshold for "collapsing" a longer time period to be shown as a break in the timeline? =

3 months. In our testing, we found that any distance longer than 250 pixels or so seemed like too much. This is our current approach to that situation.

= What themes is Threads compatible with? =

Threads has been tested with <a href="http://crowdfavorite.com/wordpress/themes/favepersonal/">FavePersonal</a>, Twenty Ten, Twenty Eleven, Twenty Twelve and Twenty Thirteen (beta). We hope it will be compatible with most themes, but cannot guarantee compatibility with any specific theme.

= Why isn't my question listed here? =

Ask them in the support forums and we'll add them here as they are answered.

== Screenshots ==

1. The timeline for a thread.
2. Notice that a post is part of a thread.
3. Editing a thread post.

== Changelog ==

= 1.0b1 =
* Initial public release.

== Upgrade Notice ==

= 1.0b1 =
Initial public release.

== Developers ==

The architecture of Threads is a custom taxonomy coupled with a "dependent" custom post type where content (description, featured image, etc.) for the taxonomy term can be stored. The <a href="https://github.com/crowdfavorite/wp-tax-post-binding">CF Taxonomy Post Type Binding</a> plugin provides the functionality to keep the post type and taxonomy term in sync with each other. The thread display is the display of the custom post type, while the taxonomy is not public.

Threads separates presentation files into views, with appropriate <a href="http://codex.wordpress.org/Plugin_API">filters</a> on each. You can override the templates, CSS, etc. used to display a thread timeline by using these filters.

Developement for Threads occurs in the <a href="https://github.com/crowdfavorite/wp-threads">public GitHub repository</a>, please collaborate with us there.
