<?php
    
    namespace Hcode\Model;

    use Hcode\Model\Product;
    use \Hcode\DB\Sql;
    use Hcode\Mailer;
    use \Hcode\Model;
    use mysql_xdevapi\Exception;
    
    class Category extends Model
    {
        public function save()
        {
            $sql = new Sql();
            $results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)",
                array(
                    ":idcategory" => $this->getidcategory(),
                    ":descategory" => $this->getdescategory()
                
                )
            );
            $this->setData($results[0]);
            category::updateFile();
            
        }
        
        public static function updateFile()
        {
            $categories = category::listAll();
            $html = [];
            foreach ($categories as $row) {
                array_push($html,
                    '<li><a href="/categories/' . $row['idcategory'] . '">' . $row['descategory'] . '</a></li>');
            }
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html",
                implode('', $html));
            
        }
        
        public static function listAll()
        {
            $sql = new Sql();
            return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
            
        }
        
        public function get($idcategory)
        {
            $sql = new Sql();
            
            $results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
                ":idcategory" => $idcategory
            ]);
            
            $this->setData($results[0]);
        }
        
        public function delete()
        {
            $sql = new Sql();
            $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
                ":idcategory" => $this->getidcategory()
            ]);
            category::updateFile();
        }
        
        public function getProducts($related = true)
        {
            $sql = new Sql();
            if ($related === true) {
               return $sql->select("SELECT * FROM tb_products WHERE idproduct IN(
                    SELECT a . idproduct FROM tb_products a INNER JOIN tb_productscategories b
                        ON a . idproduct = b . idproduct WHERE idcategory = :idcategory);", [
                            ':idcategory'=>$this->getidcategory()
                ]);
            
            } else {
                return $sql->select("SELECT * FROM tb_products WHERE idproduct NOT IN(
                    SELECT a . idproduct FROM tb_products a INNER JOIN tb_productscategories b
                        ON a . idproduct = b . idproduct WHERE idcategory = :idcategory);", [
                    ':idcategory'=>$this->getidcategory()
                ]);
            
            }
            
        }
        
        public function addProduct(Product $product){
            $sql = new Sql();
            $sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES (:idcategory, :idproduct)", [
                ":idcategory" => $this->getidcategory(),
                ":idproduct" => $product->getidproduct()
            ]);
            
        }
    
        public function removeProduct(Product $product)
        {
            $sql = new Sql();
            $sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", [
                ":idcategory" => $this->getidcategory(),
                ":idproduct" => $product->getidproduct()
            ]);
        }
    }
    
    ?>