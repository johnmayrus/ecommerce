<?php
    
    namespace Hcode\Model;
    
    use \Hcode\DB\Sql;
    use Hcode\Mailer;
    use \Hcode\Model;
    use mysql_xdevapi\Exception;
    
    class Product extends Model
    {
        public static function listAll()
        {
            $sql = new Sql();
            return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
            
        }
        
        public static function checkList($list)
        {
            
            foreach ($list as &$row) {
                $p = new Product();
                $p->setData($row);
                $row = $p->getValues();
            }
            return $list;
        }
        
        public function getValues()
        {
            $this->checkPhoto();
            
            $values = parent::getValues();
            
            return $values;
        }
        
        public function checkPhoto()
        {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR .
                "site" . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . "products" . DIRECTORY_SEPARATOR . $this->getidproduct() . ".jpg")) {
                $url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";
            } else {
                $url = "/res/site/img/product.jpg";
            }
            return $this->setdesphoto($url);
        }
        
        public static function getPage($page = 1, $itensPerPage = 10)
        {
            $start = ($page - 1) * $itensPerPage;
            $sql = new Sql();
            $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_products
            ORDER BY desproduct
            LIMIT $start, $itensPerPage;");
            
            $resultsTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
            
            return [
                'data' => $results,
                'total' => (int)$resultsTotal[0]['nrtotal'],
                'pages' => ceil($resultsTotal[0]['nrtotal'] / $itensPerPage)
            ];
        }
        
        public static function getPageSearch($search, $page = 1, $itensPerPage = 10)
        {
            
            $start = ($page - 1) * $itensPerPage;
            $sql = new Sql();
            $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_products
            WHERE desproduct LIKE :search
            ORDER BY desproduct
            LIMIT $start, $itensPerPage;
            ", [
                ':search' => '%' . $search . '%'
            ]);
            
            $resultsTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
            
            return [
                'data' => $results,
                'total' => (int)$resultsTotal[0]['nrtotal'],
                'pages' => ceil($resultsTotal[0]['nrtotal'] / $itensPerPage)
            ];
        }
        
        public function save()
        {
            $sql = new Sql();
            $results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight,
            :vllength, :vlweight, :desurl)",
                array(
                    ":idproduct" => $this->getidproduct(),
                    ":desproduct" => $this->getdesproduct(),
                    ":vlprice" => $this->getvlprice(),
                    ":vlwidth" => $this->getvlwidth(),
                    ":vlheight" => $this->getvlheight(),
                    ":vllength" => $this->getvllength(),
                    ":vlweight" => $this->getvlweight(),
                    ":desurl" => $this->getdesurl()
                )
            );
//            var_dump([$results]);exit();
            $this->setData($results[0]);
            
        }
        
        public function get($idproduct)
        {
            $sql = new Sql();
            
            $results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
                ":idproduct" => $idproduct
            ]);
            
            $this->setData($results[0]);
        }
        
        public function delete()
        {
            $sql = new Sql();
            $sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
                ":idproduct" => $this->getidproduct()
            ]);
            
        }
        
        public function setPhoto($file)
        {
            if (!$file['error']) {
                //  $parts=pathinfo($file['name'],PATHINFO_EXTENSION);
                //$parts['extension'];
                $extension = explode(".", $file['name']);
                
                $extension = end($extension);
                
                switch (strtolower($extension)) {
                    case "gif";
                        $image = imagecreatefromgif($file["tmp_name"]);
                        break;
                    case "png";
                        $image = imagecreatefrompng($file["tmp_name"]);
                        break;
                    case "webp";
                        $image = imagecreatefromwebp($file["tmp_name"]);
                        break;
                    default:
                        $image = imagecreatefromjpeg($file["tmp_name"]);
                        break;
                }
                
                $dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
                    "res" . DIRECTORY_SEPARATOR .
                    "site" . DIRECTORY_SEPARATOR .
                    "img" . DIRECTORY_SEPARATOR .
                    "products" . DIRECTORY_SEPARATOR .
                    $this->getidproduct() . ".jpg";
                
                imagejpeg($image, $dist);
                
                imagedestroy($image);
                
                $this->checkPhoto();
            }
        }
        
        public function getFromURL($desurl)
        {
            
            $sql = new Sql();
            
            $rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [
                'desurl' => $desurl
            ]);
            $this->setData($rows[0]);
        }
        
        public function getCategories()
        {
            
            $sql = new Sql();
            return $sql->select("SELECT * FROM tb_categories a INNER JOIN tb_productscategories b ON
                a.idcategory = b.idcategory WHERE b.idproduct = :idproduct", [
                ":idproduct" => $this->getidproduct()
            ]);
            
            
        }
        
    }
    
    ?>