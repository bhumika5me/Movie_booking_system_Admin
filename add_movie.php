<?php
require __DIR__ . '/db.php';

// ---- Unwraps "viewer" links (Google/Bing image search result pages) down to the
//      actual direct image file URL, so pasted search-result links still work. ----
function extract_direct_image_url(string $url): string {
    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) {
        return $url;
    }
    $host = strtolower($parts['host']);

    // Google Images "imgres" viewer page: .../imgres?...&imgurl=<encoded real url>&...
    if (strpos($host, 'google.') !== false && !empty($parts['query'])) {
        parse_str($parts['query'], $q);
        foreach (['imgurl', 'mediaurl', 'url'] as $key) {
            if (!empty($q[$key]) && filter_var($q[$key], FILTER_VALIDATE_URL)) {
                return $q[$key];
            }
        }
    }

    // Bing Images viewer page: .../images/search?...&mediaurl=<encoded real url>&...
    if (strpos($host, 'bing.com') !== false && !empty($parts['query'])) {
        parse_str($parts['query'], $q);
        if (!empty($q['mediaurl']) && filter_var($q['mediaurl'], FILTER_VALIDATE_URL)) {
            return $q['mediaurl'];
        }
    }

    return $url;
}

// ---- Converts any YouTube link (watch, youtu.be, shorts, or already embed) into
//      the /embed/ format required for the trailer to actually play in an <iframe>. ----
function get_youtube_embed_url(string $url): string {
    $patterns = [
        '/youtube\.com\/watch\?v=([A-Za-z0-9_-]{11})/',
        '/youtu\.be\/([A-Za-z0-9_-]{11})/',
        '/youtube\.com\/embed\/([A-Za-z0-9_-]{11})/',
        '/youtube\.com\/shorts\/([A-Za-z0-9_-]{11})/',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $m)) {
            return "https://www.youtube.com/embed/{$m[1]}";
        }
    }
    return $url;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_name   = trim($_POST['movie_name'] ?? '');
    $genre        = trim($_POST['genre'] ?? '');
    $duration     = trim($_POST['duration'] ?? '');
    $language     = trim($_POST['language'] ?? '');
    $director     = trim($_POST['director'] ?? '');
    $release_date = trim($_POST['release_date'] ?? '');
    $poster       = trim($_POST['poster'] ?? '');
    $trailer_link = trim($_POST['trailer_link'] ?? '');
    $status       = $_POST['status'] ?? 'Now Showing';
    $description  = trim($_POST['description'] ?? '');

    // ---- added_by: pulled from session if you have admin login set up ----
    $added_by = $_SESSION['user_id'] ?? null;

    if ($movie_name === '') $errors[] = 'Movie name is required.';
    if ($genre === '')      $errors[] = 'Genre is required.';
    if ($duration === '')   $errors[] = 'Duration is required.';
    if (!in_array($status, ['Now Showing', 'Coming Soon'], true)) $errors[] = 'Invalid status.';

    // ---- Unwrap Google/Bing "viewer" links pasted from image search results ----
    if ($poster !== '') {
        $poster = extract_direct_image_url($poster);
    }

    // ---- Poster must be a valid URL (many image hosts don't use a file extension) ----
    if ($poster !== '' && !filter_var($poster, FILTER_VALIDATE_URL)) {
        $errors[] = 'Poster must be a valid image URL (e.g. https://example.com/poster.jpg).';
    }

    // ---- Convert trailer link into an embeddable YouTube URL, if it's a YouTube link ----
    if ($trailer_link !== '') {
        $trailer_link = get_youtube_embed_url($trailer_link);
    }

    $release_date_val = $release_date !== '' ? $release_date : null;

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO nowshowing
                (movie_name, genre, duration, language, director, release_date, poster, trailer_link, status, description, added_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssssssi",
            $movie_name, $genre, $duration, $language, $director,
            $release_date_val, $poster, $trailer_link, $status, $description,
            $added_by
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: manage_movies.php?saved=1");
            exit;
        }
        $errors[] = 'Could not add movie. Please try again.';
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Movie - RedCine</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family: Arial, sans-serif; }
body { background:#0b0b0b; color:white; }

.sidebar {
    width:250px; height:100vh; background:#111; position:fixed;
    padding:20px; border-right:2px solid red;
}
.sidebar h2 { color:red; text-align:center; margin-bottom:30px; }
.sidebar a {
    display:block; color:white; text-decoration:none; padding:12px;
    margin:8px 0; background:#1a1a1a; border-radius:6px;
}
.sidebar a:hover { background:red; }

.main { margin-left:260px; padding:25px; max-width:700px; }

.topbar {
    background:#111; padding:15px 20px; border-radius:10px;
    border-left:4px solid red; margin-bottom:25px;
}
.topbar h1 { font-size:22px; }

.form-card {
    background:#1a1a1a; border-radius:10px; padding:25px;
    border-left:4px solid red;
}

.form-group { margin-bottom:16px; }
.form-row { display:flex; gap:12px; }
.form-row .form-group { flex:1; }

label {
    display:block; font-size:13px; font-weight:bold;
    margin-bottom:6px; color:#ccc; text-transform:uppercase; letter-spacing:.3px;
}

input, select, textarea {
    width:100%; padding:10px 12px; border-radius:6px;
    border:1px solid #333; background:#111; color:white; font-size:14px; outline:none;
}

textarea { resize:vertical; min-height:80px; }

input:focus, select:focus, textarea:focus {
    border-color:red;
}

.save-btn {
    background:red; color:white; border:none; padding:12px 20px;
    border-radius:6px; cursor:pointer; font-size:15px; font-weight:bold; margin-top:6px;
}

.cancel-link {
    display:inline-block; margin-left:12px; color:#aaa; text-decoration:none; font-size:14px;
}

.alert {
    padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;
    background:#3b1212; border-left:4px solid red; color:#ffc2c2;
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>RED<span style="color:white;">CINE</span></h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_movies.php">Manage Movies</a>
    <a href="admin_bookings.php">Bookings</a>
    <a href="showtimes.php">Showtimes</a>
    <a href="users.php">Users</a>
    <a href="admin_payments.php">Payments</a>
    <a href="admin_reports.php">Reports</a>
    <a href="admin_login.php">Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <h1>Add Movie</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" action="add_movie.php">

            <div class="form-group">
                <label>Movie Name</label>
                <input type="text" name="movie_name" value="<?= htmlspecialchars($_POST['movie_name'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Genre</label>
                    <input type="text" name="genre" value="<?= htmlspecialchars($_POST['genre'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Duration</label>
                    <input type="text" name="duration" placeholder="e.g. 2h 30m" value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Language</label>
                    <input type="text" name="language" value="<?= htmlspecialchars($_POST['language'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Director</label>
                    <input type="text" name="director" value="<?= htmlspecialchars($_POST['director'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Release Date</label>
                    <input type="date" name="release_date" value="<?= htmlspecialchars($_POST['release_date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Now Showing" <?= ($_POST['status'] ?? '') === 'Now Showing' ? 'selected' : '' ?>>Now Showing</option>
                        <option value="Coming Soon" <?= ($_POST['status'] ?? '') === 'Coming Soon' ? 'selected' : '' ?>>Coming Soon</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Poster Image URL</label>
                <input
                    type="url"
                    name="poster"
                    id="posterInput"
                    placeholder="https://example.com/poster.jpg"
                    value="<?= htmlspecialchars($_POST['poster'] ?? '') ?>"
                    oninput="previewPoster()"
                >
                <div style="font-size:12px; color:#777; margin-top:4px;">
                    Paste a direct image link (right-click an image online → "Copy image address").
                </div>
                <div id="posterPreviewWrap" style="margin-top:10px; display:none;">
                    <img id="posterPreview" src="" alt="Poster preview"
                         style="max-width:160px; max-height:220px; border-radius:8px; border:1px solid #333; object-fit:cover;">
                </div>
                <div id="posterError" style="display:none; color:#ff8080; font-size:12.5px; margin-top:6px;">
                    Preview unavailable (the site may block hotlinking) — the URL will still be saved.
                </div>
            </div>

            <div class="form-group">
                <label>Trailer Link</label>
                <input type="text" name="trailer_link" placeholder="https://youtube.com/watch?v=... or any YouTube link" value="<?= htmlspecialchars($_POST['trailer_link'] ?? '') ?>">
                <div style="font-size:12px; color:#777; margin-top:4px;">
                    Paste any YouTube link (watch, share, or shorts) — it'll be converted automatically so the trailer actually plays.
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="save-btn">Add Movie</button>
            <a href="manage_movies.php" class="cancel-link">Cancel</a>
        </form>
    </div>
</div>

<script>
// Unwraps Google/Bing "viewer" links (from image search results) down to the
// actual direct image file URL, so pasted search-result links still preview.
function extractDirectImageUrl(rawUrl) {
    let parsed;
    try {
        parsed = new URL(rawUrl);
    } catch (e) {
        return rawUrl;
    }
    const host = parsed.hostname.toLowerCase();

    if (host.includes('google.')) {
        const imgurl = parsed.searchParams.get('imgurl')
            || parsed.searchParams.get('mediaurl')
            || parsed.searchParams.get('url');
        if (imgurl) return imgurl;
    }

    if (host.includes('bing.com')) {
        const mediaurl = parsed.searchParams.get('mediaurl');
        if (mediaurl) return mediaurl;
    }

    return rawUrl;
}

function previewPoster() {
    const posterField = document.getElementById('posterInput');
    const rawUrl = posterField.value.trim();
    const wrap = document.getElementById('posterPreviewWrap');
    const img = document.getElementById('posterPreview');
    const errorMsg = document.getElementById('posterError');

    if (!rawUrl) {
        wrap.style.display = 'none';
        errorMsg.style.display = 'none';
        return;
    }

    const url = extractDirectImageUrl(rawUrl);

    // If we unwrapped a viewer link, clean up the visible field too, so what
    // gets saved matches what's previewed.
    if (url !== rawUrl) {
        posterField.value = url;
    }

    img.onload = function () {
        wrap.style.display = 'block';
        errorMsg.style.display = 'none';
    };
    img.onerror = function () {
        wrap.style.display = 'none';
        errorMsg.style.display = 'block';
    };
    img.referrerPolicy = 'no-referrer';
    img.src = url;
}

// Show a preview immediately if the field was pre-filled (e.g. after a validation error)
previewPoster();
</script>

</body>
</html>