<?php

/* Namespace */

namespace App\Models;


class Users
{
    /**
     *. fonction inscription verif
     */
    public function registration()
    {
        //. INSCRIPTION
        if (isset($_POST['formRegistration'])) {
            //. NETTOYAGE 
            $lastname = filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
            $firstname = filter_var($_POST['firstname'], FILTER_SANITIZE_STRING);
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
            $passHash = password_hash($password, PASSWORD_DEFAULT);



            $data_validated = true;


            //. VERIFICATION
            if (filter_var($_POST["lastname"], FILTER_SANITIZE_STRING) === false) {
                $data_validated = false;
            } elseif (filter_var($_POST["firstname"], FILTER_SANITIZE_STRING) === false) {
                $data_validated = false;
            } elseif (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL) === false) {
                $data_validated = false;
            } elseif (filter_var($_POST["password"], FILTER_SANITIZE_STRING) === false) {
                $data_validated = false;
            }

            //. Connexion Base de données
            include  __DIR__ . "/../core/database.php";


            //. INSCRIPTION UTILISATEURS
            if ($data_validated === true) {
                $query = $dbh->prepare('INSERT INTO users(lastname, firstname, email, password) VALUES (?, ?, ?, ?)');
                $result = $query->execute([ucfirst($lastname), ucfirst($firstname), $email, $passHash]);
                echo $passHash;
            }
        }
    }


    /**
     * .fonction connexion verif
     */
    public function connection()
    {

        include  __DIR__ . "/../core/database.php";

        if (isset($_POST['formConnection'])) {


            $mailConnect = filter_var($_POST['mailConnect'], FILTER_SANITIZE_STRING);
            $passwordConnect = filter_var($_POST['passwordConnect'], FILTER_SANITIZE_STRING);
            $passConnectHash = password_verify($passwordConnect, '$2y$10$teCcsm4.LEzHNZwGU6kvdOay2MDcjGVEJaJmm0JweY6qvQXcjFvXG');

            var_dump($passConnectHash);


            if (!empty($mailConnect) && !empty($passwordConnect)) {
                $userRequest = $dbh->prepare('SELECT * FROM users WHERE email = ? AND password = ?');
                $userRequest->execute(array($mailConnect, $passConnectHash));
                $userRequest->rowCount();


                if ($passConnectHash == true) {
                    $userInfo = $userRequest->fetch();
                    $id = $_SESSION['id'] = $userInfo['id'];
                    $_SESSION['lastname'] = $userInfo['lastname'];
                    $_SESSION['firstname'] = $userInfo['firstname'];
                    $_SESSION['email'] = $userInfo['email'];
                    $_SESSION['password'] = $userInfo['password'];
                    $_SESSION['is_connected'] = 1;


                    // passage isConnected a 1
                    $userRequest = $dbh->prepare('UPDATE users SET is_connected = ? WHERE id = ?');
                    $userRequest->execute([1, $id]);

                    header('Location: accueil?id=' . $id);
                } else {
                    header('location: accueil');
                }
            }
        }
    }
    public function updatePerso()
    {
        //connexion data base 
        include  __DIR__ . "/../core/database.php";

        $lastname = filter_var($_POST["lastname"], FILTER_SANITIZE_STRING);
        $firstname = filter_var($_POST["firstname"], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
        $password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);


        $query = $dbh->prepare(" UPDATE users SET lastname = ?, firstname = ?, email = ?, password = ? WHERE id = ? ");
        $query->execute([$lastname, $firstname, $email, $password, $_SESSION['id']]);

        $_SESSION['lastname'] = $lastname;
        $_SESSION['firstname'] = $firstname;
        $_SESSION['email'] = $email;
        $_SESSION['password'] = $password;



        header('location: accueil');
    }



    /* fonction deconexion  */
    public function disconnection()
    {
        include  __DIR__ . "/../core/database.php";

        // passage isConnected a 0
        $userRequest = $dbh->prepare('UPDATE users SET is_connected = ? WHERE id = ?');
        $userRequest->execute([0, $_SESSION['id']]);

        session_destroy();
        header('Location: accueil');
    }
}
