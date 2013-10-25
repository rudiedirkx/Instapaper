<?php

require 'inc.bootstrap.php';

is_logged_in(true);



// Archive
if ( isset($_GET['archive']) ) {
	$rsp = do_archive($_GET['archive']);
	if ( $rsp ) {
		$bookmarks = get_bookmarks();
		foreach ( $bookmarks as $i => $bm ) {
			if ( $bm->bookmark_id == $_GET['archive'] ) {
				unset($bookmarks[$i]);
			}
		}
		set_bookmarks($bookmarks);

		exit('OK');
	}

	exit('Some error!?');
}

// Favorite
else if( isset($_GET['favorite']) ) {
	$_id = $_GET['favorite'];
	$bookmarks = get_bookmarks();
	$bookmark = $bookmarks['bm_' . $_id];
	$action = $bookmark->starred ? 'unstar' : 'star';
	$rsp = do_favorite($_GET['favorite'], $action);
	if ( $rsp ) {
		$bookmark->starred = (int)!$bookmark->starred;
		set_bookmarks($bookmarks);

		exit('OK');
	}

	exit('Some error!?');
}



// List bookmarks
$refresh = !empty($_COOKIE['iprefr']);
if ( $refresh ) {
	setcookie('iprefr', '', 1);
}

$refreshed = $refresh;
$bookmarks = get_bookmarks($refreshed);
$total = count($bookmarks);
$bookmarks = array_slice($bookmarks, 0, 20);

?>
<title>Instapaper</title>
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
<meta name="viewport" content="width=device-width, initial-scale=0.5" />
<meta charset="utf-8" />
<style><?= get_css() ?></style>
<?php

$fresh = $refreshed ? ' (fresh)' : '';
echo '<h3>' . count($bookmarks) . ' / ' . $total . ' bookmarks' . $fresh . ' <span class="sub">(<a onclick="document.cookie=\'iprefr=1\';" href="">refresh</a>)</span></h3>';
echo '<ol class="bookmarks">';
foreach ( $bookmarks as $bm ) {
	$id = $bm->bookmark_id;

	$_url = parse_url($bm->url);
	$host = substr($_url['host'], 0, 4) == 'www.' ? substr($_url['host'], 4) : $_url['host'];

	$classes = array();
	$bm->starred && $classes[] = 'is-favorite';

	echo '<li class="' . implode(' ', $classes) . '">';
	echo '<div class="archive"><a href="?archive=' . $id . '">A</a></div>';
	echo '<div class="link">';
	echo '  <a href="' . $bm->url . '">' . $bm->title . '</a>';
	echo '  <div class="favorite"><a href="?favorite=' . $id . '">♥</a></div>';
	echo '</div>';
	echo '<div class="created">' . date(DT, $bm->time) . '</div>';
	echo '<div class="host"><a href="https://www.instapaper.com/read/' . $id . '">' . $host . '</a></div>';
	echo '<div class="edit"><a href="https://www.instapaper.com/edit/' . $id . '">E</a></div>';
	echo '</li>';
}
echo '</ol>';

?>

<p>Bookmarklet: <a href="bookmarklet.php" onmouseover="this.onmouseover=null; this.href='javascript: document.head.appendChild((function(el) { el.src=\'BASE?url=\' + encodeURIComponent(location.href) + \'&title=\' + encodeURIComponent(document.title); return el; })(document.createElement(\'script\'))); void(0)'.replace(/BASE/, this.href)">Read me later</a></p>

<script>
<?= file_get_contents('framework.js') ?>
</script>
<script>
document.querySelector('.bookmarks').addEventListener('click', function(e) {
	if ( e.target.is('.archive > a') ) {
		e.preventDefault();
		ajax(e.target.href, function(t) {
			t == 'OK' ? location.reload() : alert(t);
		});
	}

	else if ( e.target.is('.favorite > a') ) {
		e.preventDefault();
		ajax(e.target.href, function(t) {
			t == 'OK' ? e.target.parentNode.parentNode.parentNode.classList.toggle('is-favorite') : alert(t);
		});
	}
});
</script>
