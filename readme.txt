=== Category Post Navigation ===
Contributors: atakanau
Donate link: https://buymeacoffee.com/atakanau
Tags: navigation, category, pagination, single post, block
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Numeric, prev/next and mixed navigation between published posts of the current post's category on single post views.

== Description ==

Category Post Navigation helps visitors move through the posts of a category while reading. On a single post view the plugin builds a numbered list of the published posts in that category, marks the current post and links to the others.

Features:

* Four usage paths: `[category_post_navigation]` shortcode, `catpnav_the_navigation()` / `catpnav_get_navigation()` template tags, the `catpnav/navigation` Gutenberg block (also usable in block theme templates), and optional automatic display before/after the content.
* Three modes: numeric list, prev/next links, or mixed — prev/next links combined with the numeric list in one navigation.
* Three scopes: all posts of the category, a window around the current post, or a fixed-total window that shifts sideways near the edges so the item count stays constant.
* Multi-category priority: choose how the context category is picked when a post has several categories — first/lowest ID (default), deepest child category, or the category with the most posts.
* Custom previous/next labels with translatable defaults.
* Long lists collapse with an ellipsis: the first two, the last two and the three neighbours of the current post stay visible (All posts scope).
* Sorting by date or title, ascending or descending.
* Numbering direction: display numbers ascend or descend along the post list.
* Accessible markup: `nav` element, list structure, `aria-current="page"` on the active item, visible keyboard focus.
* Settings page under Settings → Post Navigation, split into Basic and Advanced sections with one-click quick-start presets and a live preview of the resulting navigation; shortcode attributes can override every setting per usage.
* Translation-ready with the `category-post-navigation` text domain; no third-party plugin dependencies.
* Two lightweight filters for developers (`catpnav_window_items`, `catpnav_navigation_html`).

Performance: the navigation runs two small queries per single post view (one ID list, one batched priming query for the rendered items) and no cache layer, so lists are always current.

= Example pagination cases =

* 50-post category, "All posts" scope, ellipsis on, current post 9th, ascending numbering: `1 2 … 7 8 [9] 10 11 … 49 50`. The current post is emphasised; long runs collapse to a single "…" item.
* Category with 8 posts — below the collapse threshold, the full list is rendered: `1 2 3 [4] 5 6 7 8`.
* "Posts around the current post" scope with 5 items per side and the current post second: `1 [2] 3 4 5 6 7`.
* "Fixed total" scope with 7 items and the current post second of 50: `1 [2] 3 4 5 6 7`. With the current post 49th the window shifts but the count stays 7: `44 45 46 47 48 49 [50]`.
* Mixed mode prints the prev/next neighbours plus the numbers: `← Previous 1 2 [3] 4 5 Next →`. A missing direction is omitted entirely, so there are no empty links at the first or last post.
* Prev/next mode prints only the labelled links; the same first/last rule applies.

`[9]` marks the emphasised current post.

== Settings ==

Basic:

* Quick start — one-click presets (Classic list, Nearby window, Fixed window, Prev / Next, Mixed) that fill the fields below; adjust anything and save. Layout, ordering and numbering stay untouched.
* Live preview — sample-data rendering of the navigation beside the form; it updates as settings change.
* Categories — "Limit navigation to selected categories" checkbox plus a multi-select; filter off or empty selection means all categories.
* Mode — Numeric, Prev / Next, or Mixed (prev/next links plus the numeric list).
* Previous / Next label — custom text for the prev/next links; empty uses the translated default ("Previous" / "Next"). Effective in the Prev / Next and Mixed modes.
* Automatic display — independent "Before content" and "After content" checkboxes; both off by default.

Advanced:

* Multi-category priority — First category (lowest ID, default), Deepest (child) category, or Category with the most posts; ties resolve to the lowest term ID.
* Scope — All posts (default), Posts around the current post, or Fixed total.
* Items per side — 1–20 (default 5); the per-side count for "Posts around", the total item count for "Fixed total".
* Order by — Date or Title; Direction — Ascending or Descending (default descending: newest post first).
* Layout — Horizontal or Vertical; Numbering — Ascending or Descending display numbers (default ascending).
* Show titles — off by default; Use ellipsis — on by default, effective in the "All posts" scope only.

== Shortcode attributes ==

Every setting can be overridden per usage; unknown or invalid attributes fall back to the settings:

`[category_post_navigation category="news-and-updates" mode="mixed" scope="fixed" around="9" orderby="date" order="ASC" numbering="descending" show_title="false" ellipsis="false" layout="vertical" multi_cat="deepest" prev="Back" next="Forward"]`

* `category` — category slug or term ID.
* `scope` — `all`, `around` or `fixed`; `around` — 1–20.
* `mode` — `numeric`, `prevnext` or `mixed`; `layout` — `horizontal` or `vertical`; `numbering` — `ascending` or `descending`.
* `orderby` — `date` or `title`; `order` — `ASC` or `DESC`.
* `show_title` / `ellipsis` — `true` or `false`.
* `multi_cat` — `first`, `deepest` or `most_posts`.
* `prev` / `next` — custom previous/next link text.

The template tags accept the same keys, e.g. `catpnav_the_navigation(['mode' => 'mixed', 'prev' => 'Back'])`.

== Gutenberg block ==

The `catpnav/navigation` block renders the same navigation as the shortcode and the template tags, through the very same renderer. Insert it into post content or, in block themes, directly into templates with the Site Editor.

* Sidebar overrides: category (populated from the site's categories), display mode (numeric, prev/next, mixed) and window (all posts, nearby window, fixed total with 1–20 items). Every control starts at "Use settings default"; set values apply to that block instance only, and multiple instances can carry different overrides.
* Live preview: the editor renders the block server-side, exactly like the front end. Where no single-post context exists (for example in the Site Editor), a placeholder explains what the block needs instead of showing a broken preview.

== Installation ==

1. Upload the `category-post-navigation` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
2. Activate the plugin.
3. Open Settings → Post Navigation and choose your defaults.
4. Use the `[category_post_navigation]` shortcode in post content, place `catpnav_the_navigation()` in a template, insert the `catpnav/navigation` block, or enable automatic display.

== Frequently Asked Questions ==

= My post has more than one category. Which one is used? =

The "Multi-category priority" setting (Advanced) decides. First category (lowest ID): the post's categories are ordered by term ID (ascending) and the lowest wins — this is the original rule and stays the default. Deepest (child) category: the most specific child category wins. Category with the most posts: the largest category wins. Ties always resolve to the lowest term ID, and the "Categories" filter still restricts the candidates; if none qualifies, the navigation stays hidden.

= Will the numbering update when I add, delete or move posts? =

Yes. The list is built live on every single post view; there is no cache to clear.

= How do I keep the list short on very large categories? =

Keep the ellipsis setting on (default). With the "All posts" scope, only the first two, the last two and the three neighbours of the current post are rendered; everything between collapses into a single "…" item. Alternatively use the "Posts around" or "Fixed total" scope, which are limited by the "Items per side" value.

= What does the "Fixed total" scope do? =

The window always shows the same number of items: the "Items per side" value, interpreted as the total in this scope. While the current post moves through the list, the window shifts sideways near the start and the end instead of shrinking, so the navigation keeps its size on every post.

= What does descending numbering do? =

With the default ascending numbering the first post in the list shows number 1. With descending numbering the numbers run the other way: the first post in the list shows the total count and the last post shows 1, which is handy when a series is sorted newest-first but should be numbered from its first part.

= What happens with one or zero posts in a category? =

Nothing is printed. The navigation needs at least two published posts to be useful.

= How do I remove shortcode leftovers from posts? =

Deactivating the plugin keeps your settings; uninstalling removes them. Shortcode text left in post content stays as plain text — remove it with the editor.

= Can developers change the output? =

Two lightweight filters: `catpnav_window_items` (the computed window items before rendering; receives the items and the effective config and must return an array) and `catpnav_navigation_html` (the final HTML string; receives the HTML, the items and the config and must return a string).

== Screenshots ==

1. Settings screen with quick-start presets, category filter, mode and label fields, automatic display checkboxes and the live preview pane.
2. Live previews of the navigation in the numeric, prev/next and mixed modes.

== Changelog ==

= 0.1.0 =

* First release: shortcode, template tags, automatic display, numeric, prev/next and mixed modes, all/around/fixed scopes, ellipsis, ascending/descending numbering, multi-category priority, custom prev/next labels, quick-start presets, live settings preview, Basic/Advanced settings screen, lightweight developer filters, Settings link on the Plugins screen, uninstall cleanup.
* `catpnav/navigation` Gutenberg block: live server-side preview in the editor, per-block overrides for category, mode and window with clean settings fallback, a placeholder preview where no post context exists, and block theme (FSE) support.
* Editor and block placeholder strings and Turkish translations.