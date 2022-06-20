<?php
    
    namespace Hcode\Model;
    
    use \Hcode\DB\Sql;
    use Hcode\Mailer;
    use \Hcode\Model;
    use mysql_xdevapi\Exception;
    
    class User extends Model
    {
        const SESSION = 'User';
        const SECRET = 'JohnMayrus_Secret';
        const CODE = 'AES-128-ECB';
        
        public static function Login($login, $password)
        {
            $sql = new sql();
            $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
                ":LOGIN" => $login
            ));
            if (count($results) === 0) {
                throw new \Exception("Usuário ou senha inválida.");
            }
            $data = $results[0];
            if (password_verify($password, $data["despassword"]) === true) {
                $user = new User();
                $user->setData($data);
                $_SESSION[User::SESSION] = $user->getValues();
                return $user;
            } else {
                throw new \Exception("Usuário ou senha inválida.");
            }
            
        }
        
        public static function verifyLogin($inadmin = true)
        {
            if (
                !isset($_SESSION[User::SESSION])
                ||
                !$_SESSION[User::SESSION]
                ||
                !(int)$_SESSION[User::SESSION]["iduser"] > 0
                ||
                (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
            ) {
                header("location: /admin/login");
                exit();
            }
            
        }
        
        public static function logout()
        {
            unset($_SESSION[User::SESSION]);
            header("location: /admin/login");
            exit();
            
        }
        
        public static function listAll()
        {
            $sql = new Sql();
            return $sql->select("SELECT * FROM tb_users AS tb INNER JOIN tb_persons AS tp ON tb.idperson=tp.idperson ORDER BY tp.desperson");
            
        }
        
        public static function getUser($iduser)
        {
            $sql = new Sql();
            return $sql->select("SELECT * FROM tb_users AS tb INNER JOIN tb_persons AS tp ON tb.idperson=tp.idperson WHERE tb.iduser = :IDUSER",
                array(
                    ":IDUSER" => $iduser
                ));
            
        }
        
        public static function getForgot($email)
        {
            $sql = new Sql();
            $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email;",
                array(
                    ":email" => $email
                ));
            if (count($results) === 0) {
                throw new \Exception("Não foi possivel recuperar a senha.");
            } else {
                $data = $results[0];
                $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                    "iduser" => $data["iduser"],
                    "desip" => $_SERVER["REMOTE_ADDR"]
                ));
                if (count($results2) === 0) {
                    throw new \Exception("Não foi possivel recuperar a senha.");
                } else {
                    $dataRecovery = $results2[0];
                    $code = base64_encode(self::secure($dataRecovery["idrecovery"]));
                    $link = "https://www.flordecacto.com.br/admin/forgot/reset?code=$code";
                    $mailer = new Mailer($data["desemail"], $data["desperson"],
                        "Redefinir Senha da Flor de cacto story", "forgot",
                        array(
                            "name" => $data["desperson"],
                            "link" => $link
                        ));
                    $mailer->send();
                    return $data;
                }
            }
        }
        
        public static function secure($value, $encrypt = true)
        {
            if (
                in_array(self::CODE, openssl_get_cipher_methods())
                || in_array(strtolower(self::CODE), openssl_get_cipher_methods())
            ) {
                return $encrypt ? openssl_encrypt($value, self::CODE, self::SECRET) : openssl_decrypt($value,
                    self::CODE, self::SECRET);
            }
            return null;
        }
        
        public static function validForgotDecrypt($code)
        {
            $sql = new Sql();
            $results = $sql->select("SELECT * FROM tb_userspasswordsrecoveries a INNER JOIN tb_users b USING(iduser)
            INNER JOIN tb_persons c USING(idperson) WHERE a.idrecovery = :idrecovery AND a.dtrecovery IS
            NULL AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >=NOW()", array(
                ":idrecovery" => self::secure($code, false),
            ));
            if (count($results) === 0) {
                throw new \Exception("Não foi possível recuperar a senha.");
            } else {
                return $results[0];
            }
            
        }
        
        public function save()
        {
            $sql = new Sql();
            $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
                array(
                    ":desperson" => $this->getdesperson(),
                    ":deslogin" => $this->getdeslogin(),
                    ":despassword" => $this->getdespassword(),
                    ":despassword" => password_hash($this->getdespassword(), PASSWORD_DEFAULT),
                    ":desemail" => $this->getdesemail(),
                    ":nrphone" => $this->getnrphone(),
                    ":inadmin" => $this->getinadmin()
                )
            );
            $this->setData($results[0]);
        }
        
        public function get($iduser)
        {
            $sql = new Sql();
            $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser",
                array(":iduser" => $iduser));
            $this->setData($results[0]);
        }
        
        public function upDate()
        {
            $sql = new Sql();
            $results = $sql->select("CALL sp_usersupdate_save(:iduser,:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
                array(
                    ":iduser" => $this->getiduser(),
                    ":desperson" => $this->getdesperson(),
                    ":deslogin" => $this->getdeslogin(),
                    ":despassword" => $this->getdespassword(),
                    ":despassword" => password_hash($this->getdespassword(), PASSWORD_DEFAULT),
                    ":desemail" => $this->getdesemail(),
                    ":nrphone" => $this->getnrphone(),
                    ":inadmin" => $this->getinadmin()
                )
            );
            $this->setData($results[0]);
        }
        
        public function delete()
        {
            $sql = new Sql();
            $results = $sql->select("CALL sp_users_delete(:iduser)",
                array(
                    ":iduser" => $this->getiduser()
                ));
        }
        public static function setForgotUsed($idrecorevy){
            $sql = new Sql();
            $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",array(
                ":idrecovery"=>$idrecorevy
            ));
        }
        public function setpassword($password){
            $sql = new Sql();
            $sql->query("UPDATE tb_users SET despassword= :password WHERE iduser = :iduser",array(
                ":password"=>$password,
                ":iduser"=>$this->getiduser()
            ));
        }
    }
    
    ?>