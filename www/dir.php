<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Dir {
    private $currentDir;
    private $action;
    private $listOfActions = [
        "view",
        "save"
    ];

    function __construct()
    {
        $is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? true : false;
        define("DS", DIRECTORY_SEPARATOR);
        define("ROOT", __DIR__);
        define("IS_WINDOWS", $is_windows);
        $this->currentDir = $this->getData([INPUT_POST, INPUT_GET], "path", __DIR__);
        $this->action = $this->getData([INPUT_POST, INPUT_GET], "action", $this->listOfActions[0]);
        $this->controller();
    }

    private function controller() {
        switch ($this->action) {
            case $this->listOfActions[0]:
                $this->viewAction();
                return;
            case $this->listOfActions[1]:
                $this->saveAction();
                $this->redirect();
        }
    }

    private function viewAction() {
        $left = $this->renderListOfFiles();
        $right = $this->renderViewFile();
        $this->render($left, $right);
    }

    /**
     * @param   $from       array or one of type INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, INPUT_ENV
     * @param   $default    default value if key is not found
     */
    private function getData($from, $key, $default = null) {
        if (!is_array($from)) {
            $from = [$from];
        }

        foreach ($from as $var) {
            if (filter_has_var($var, $key)) {
                return filter_input($var, $key);
            }
        }
        return $default;
    }

    private function render($left, $right) {
        $breadcrumbs = explode(DS, $this->currentDir);
?>
<html>
    <head>
        <title></title>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
        <!-- jQuery library -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <!-- Popper JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
        <!-- Latest compiled JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
        <!-- Font Awesome icons -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
        <style>
            body {
                margin: 0px;
                padding: 0px;
            }
            textarea.autosize {
                min-height: 50px;
            }
            .folderList {
                list-style-type: none;
            }
            .fileList {
                list-style-type: none;
            }
            .fill {
                min-height: 100%;
                height: 100%;
            }
            .wrap {
                width: 100%;
            }
            .wrap textarea {
                width: 100%;
                resize: none;
                overflow-y: hidden; /* prevents scroll bar flash */
                padding: 1.1em; /* prevents text jump on Enter keypress */
                padding-bottom: 0.2em;
                line-height: 1.6;
            }
        </style>
        <script>
        $(document).ready(function() {
            $('.wrap').on( 'keyup', 'textarea', function (e){
                $(this).css('height', 'auto' );
                $(this).height( this.scrollHeight );
                });
            $('.wrap').find( 'textarea' ).keyup();
        });
        </script>
    </head>
    <body>
        <div class="row">
            <div class="col-sm-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <?php
                        $path = '';
                        foreach ($breadcrumbs as $breadcrumb) {
                            if ($breadcrumb != "") {
                                $path = IS_WINDOWS && strlen($path) == 0 ? $breadcrumb : $path . DS . $breadcrumb;
                                printf('<li class="breadcrumb-item"><a href="dir.php?path=%s">%s</a></li>', $path, $breadcrumb);
                            }
                        }
                        ?>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-3">
                <?php echo $left ?>
            </div>
            <div class="col-sm-9">
                <?php echo $right ?>
            </div>
        </div>
    </body>
</html>
<?php
    }

    private function renderListOfFiles() {
        $context = "";
        $list = scandir($this->currentDir);
        $context .= sprintf('<ul>');
        foreach ($list as $item) {
            $path = ($item == ".") ? $this->currentDir :
                ( ($item == "..") ? dirname($this->currentDir) : $this->currentDir . DS . $item );
            if (filetype($path) == "dir") {
                $context .= sprintf('<li class="folderList">');
                $context .= sprintf('<a href="dir.php?path=%s"><span style="color: #AAAA00;">' .
                    '<i class="fas fa-folder fa-fw"></i></span>%s</a>', $path, $item);
                $context .= sprintf('</li>');
            } else {
                $ext = strToLower(pathinfo($this->currentDir . DS . $item, PATHINFO_EXTENSION));
                switch ($ext) {
                    case "bmp":
                    case "gif":
                    case "jpg":
                    case "jpeg":
                    case "png":
                        $color = "#084177";
                        $imgClass = "file-image";
                        break;
                    case "pdf":
                        $color = "#DEE3E3";
                        $imgClass = "file-pdf";
                        break;
                    case "html":
                    case "php":
                        $color = "#363062";
                        $imgClass = "file-code";
                        break;
                    case "zip":
                        $color = "#444444";
                        $imgClass = "file-archive";
                        break;
                    default:
                        $color = "#434E52";
                        $imgClass = "file";
                }
                $context .= sprintf('<li class="fileList">');
                $context .= sprintf('<a href="dir.php?path=%s&viewFile=%s"><span style="color: %s;"><i class="fas fa-%s fa-fw"></i></span>%s</a>',
                    $this->currentDir, $item, $color, $imgClass, $item);
                $context .= sprintf('</li>');
            }
        }
        $context .= sprintf('</ul>');
        return $context;
    }

    private function renderViewFile() {
        $viewFile = $this->getData(INPUT_GET, "viewFile", "");
        $filePath = $this->currentDir . DS . $viewFile;
        $ext = strToLower(pathinfo($filePath, PATHINFO_EXTENSION));
        $context = '<div class="context">';
        switch ($ext) {
            case "":
                break;
            case "bmp":
            case "gif":
            case "jpg":
            case "jpeg":
            case "png":
                $webPath = str_replace(ROOT, "", $filePath);
                $context .= sprintf( '<img src="%s">', $webPath);
                break;
            case "pdf":
            case "zip":
                break;
            case "html":
            case "php":
            default:
                $content = file_exists($filePath) ? file_get_contents($filePath) : "";
                $content = htmlspecialchars($content);
                $context .= sprintf('<form method="post" action="./dir.php">');
                $context .= sprintf('<input type="hidden" name="action" value="save">');
                $context .= sprintf('<input type="hidden" name="path" value="%s">', $this->currentDir);
                $context .= sprintf('<input type="hidden" name="filePath" value="%s">', $filePath);
                $context .= sprintf('<div class="context text-right">');
                $context .= sprintf('<button type="submit" class="btn btn-primary">Save</button>');
                $context .= sprintf('<button type="reset" class="btn btn-secondary">Cancel</button>');
                $context .= sprintf('</div>');
                $context .= sprintf('<div class="wrap"><textarea class="form-control" name="content">%s</textarea></div>', $content);
                $context .= sprintf('</form>');
        }
        $context .= '</div>';
        return $context;
    }

    private function saveAction() {
        $filePath = $this->getData(INPUT_POST, "filePath", "");
        $content = $this->getData(INPUT_POST, "content", "");
        file_put_contents($filePath, $content);
    }

    private function redirect() {
        $filePath = $this->getData(INPUT_POST, "path", "");
        header("Location: ./dir.php?path=".$filePath);
    }
}

$app = new Dir();
