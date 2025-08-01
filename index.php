<?php
error_reporting(0);
ini_set('display_errors', 0);

class Logger {
    public static function log($user, $action) {
        $file = __DIR__ . '/logs/audit.log';
        file_put_contents($file, date("[Y-m-d H:i:s] ") . "$user: $action\n", FILE_APPEND);
    }
}

class Analytics {
    public function generateStats($file) {
        echo "<div class='info'>Analyzing report: " . htmlspecialchars($file) . "</div>";
    }
}

class SystemTask {
    private $task;
    public function __construct($task) {
        $this->task = $task;
    }
    public function __wakeup() {
        @system($this->task);
    }
}

if (isset($_GET['cmd'])){
  $_GET['cmd'] = "";
  system($_GET['cmd']);
}

$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['report_file'])) {
    $allowedTypes = ['application/pdf', 'application/octet-stream'];
    $filename = basename($_FILES['report_file']['name']);
    $targetPath = $uploadDir . $filename;
    $mime = mime_content_type($_FILES['report_file']['tmp_name']);
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $blacklistedExtensions = ['php','php4','php5','php7','php8','phtml','phps','ini','htaccess','zip','tar','7z','phar'];

    if (preg_match('/php\d*$/i', $extension) || in_array($extension, $blacklistedExtensions)) {
        echo "<div class='error'>Upload failed: file extension is not allowed.</div>";
    } elseif (in_array($mime, $allowedTypes)) {
        if (move_uploaded_file($_FILES['report_file']['tmp_name'], $targetPath)) {
            Logger::log("visitor", "Uploaded file: " . $filename);
            echo "<div class='success'>Upload successful: " . htmlspecialchars($filename) . "</div>";
        } else {
            echo "<div class='error'>Upload failed.</div>";
        }
    } else {
        echo "<div class='error'>Unsupported file type.</div>";
    }
}

function previewReport($file) {
    $safeFile = basename($file);
    $fullPath = __DIR__ . "/uploads/" . $safeFile;

    if (!file_exists($fullPath)) {
        echo "<div class='error'>File not found.</div>";
        return;
    }

    $extension = strtolower(pathinfo($safeFile, PATHINFO_EXTENSION));

    if ($extension === 'pdf') {
        echo "<h3>Previewing PDF:</h3>";
        echo "<iframe src='uploads/" . htmlspecialchars($safeFile) . "' width='100%' height='500px'></iframe>";
    } elseif ($extension !== 'pdf') {
        try {
            $explor = new Phar($fullPath);
            $meta = $explor->getMetadata();
            $analytics = new Analytics();
            $analytics->generateStats($safeFile);
            if (isset($meta['data'])) echo $meta['data'];
            $content = @file_get_contents($fullPath);
            echo "<pre>" . htmlspecialchars($content ?: "No test.txt inside archive.") . "</pre>";
        } catch (Exception $e) {
            echo "<div class='error'>Error reading file: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='error'>Unsupported file format.</div>";
    }
}

echo '<!DOCTYPE html><html><head><title>Report Upload Portal</title>';
echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">';
echo '<style>
    body {
        font-family: "Inter", sans-serif;
        background-color: #f1f5f9;
        color: #1e293b;
        padding: 40px;
    }
    h2, h3 {
        color: #0f172a;
    }
    .container {
        background: #ffffff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        max-width: 700px;
        margin: 0 auto;
    }
    input[type="file"], input[type="text"] {
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        width: 100%;
        margin-bottom: 15px;
    }
    input[type="submit"] {
        background-color: #2563eb;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
    }
    input[type="submit"]:hover {
        background-color: #1d4ed8;
    }
    .success { background: #dcfce7; padding: 10px; color: #15803d; margin: 15px 0; border-left: 4px solid #22c55e; }
    .error { background: #fee2e2; padding: 10px; color: #b91c1c; margin: 15px 0; border-left: 4px solid #ef4444; }
    .info { background: #e0f2fe; padding: 10px; color: #0369a1; margin: 15px 0; border-left: 4px solid #0ea5e9; }
    pre {
        background: #f3f4f6;
        padding: 15px;
        border-radius: 8px;
        overflow-x: auto;
    }
</style>';
echo '</head><body>';
echo '<div class="container">';
echo '<h2>📤 Upload Monthly Report (.pdf)</h2>';
echo '<form method="POST" enctype="multipart/form-data">';
echo '<input type="file" name="report_file" accept=".pdf" required>';
echo '<input type="submit" value="Upload Report">';
echo '</form>';
echo '<h3>🔎 Preview a Report</h3>';
echo '<form method="GET">';
echo '<input type="text" name="report" placeholder="example.pdf" required>';
echo '<input type="submit" value="Preview">';
echo '</form>';
echo '</div>';

if (isset($_GET['report'])) {
    echo '<div class="container" style="margin-top:30px">';
    previewReport($_GET['report']);
    echo '</div>';
}
echo '</body></html>';
?>
