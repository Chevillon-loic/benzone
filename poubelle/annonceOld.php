<?php

namespace App\Controllers;

use App\Controllers\Component\Menu;


class Annonce
{
  protected $advert;
  protected $bidder;

  public function databaseGetAdverts()
  {
    //. Connexion Base de données
    include  __DIR__ . "/../core/database.php";
    $this->advert = $dbh->query("SELECT
      a.id,
      a.actual_price,
      a.start_date,
      a.final_date,
      a.description,
      a.picture,
      a.owner_id,
      a.bidder_id,
      c.id as car_id,
      c.brand,
      c.model,
      c.power,
      c.vehicle_year,
      c.vehicle_km,
      u.lastname as ownerln,
      u.firstname as ownerfn
  FROM
      adverts a
  INNER JOIN 
      car c
  ON c.id = a.car_id
  INNER JOIN
      users u
  ON a.owner_id = u.id
  WHERE a.id = $_GET[id]
  ")->fetchAll(\PDO::FETCH_ASSOC);
    $this->advert = $this->advert[0];

    if (!is_null($this->advert['bidder_id'])) {
      $this->bidder = $dbh->query("SELECT
      b.lastname as bidderln,
      b.firstname as bidderfn
      FROM
      users b
      WHERE b.id = {$this->advert['bidder_id']}
    ")->fetchAll(\PDO::FETCH_ASSOC);
      $this->bidder = $this->bidder[0];
    } else {
      $this->bidder = [
        'lastname' => 'Aucun',
        'firstname' => 'gagnant'
      ];
    };
    //$this->advert['bidder_id'] ?? $this->advert['bidder_id'] = 1;
  }

  public function databaseSetActualPrice()
  {
    //. Connexion Base de données
    include  __DIR__ . "/../core/database.php";

    //nettoyage et validation
    $price = $_POST["new_price"];
    $price = filter_var($price, FILTER_SANITIZE_NUMBER_INT);
    $price = filter_var($price, FILTER_VALIDATE_INT);

    if ($price > $_POST['actual_price']) {
      $query = $dbh->prepare('UPDATE
      `adverts`
      SET
      `actual_price` = ?,
      `bidder_id` = ?
      WHERE
      id = ?');
      $query->execute([$price, $_SESSION['id'], $_POST['id']]);
      header('location: annonce?id=' . $_POST["id"]);
    } else {
      header('location: annonce?id=' . $_POST["id"]);
    }
  }

  // function de transformation date
  public function createDateFrom($date)
  {
    return new \DateTime($date);
  }

  public function checkDate()
  {
    $now = new \DateTime();
    $date = new \DateTime($this->advert['final_date']);
    if ($date > $now) {
      return true;
    } else {
      return false;
    }
  }

  //Affichage de la page annonce
  public function render()
  {
?>
    <!DOCTYPE html>
    <html>

    <head>
      <meta charset="utf-8">
      <title>Annonce</title>
      <link rel="stylesheet" href="assets/styles/style.css" />
      <link rel="stylesheet" href="assets/styles/annonce.css" />
    </head>

    <body>
      <?php
      //Ajout Header
      include_once __DIR__ . "/Component/header.php";
      new Menu();

      //Récup infos from Database
      $this->databaseGetAdverts();
      ?>
      <div id="mainContainer">
        <a class="btnRetour" href="accueil"> <img src="assets/img/arrow.svg" alt=""> Retour</a>
        <!-- Titre -->
        <h1>
          <?= $this->advert['brand']; ?>
          <?= $this->advert['model']; ?> -
          <?= $this->createDateFrom($this->advert['vehicle_year'])->format('Y'); ?>
        </h1>

        <!-- Premier paragraphe -->
        <p class="termineAndEnchere"><?php if ($this->checkDate()) { ?>
            <span class="terminele">Termine le</span> <span class="termineDate"> <?= $this->createDateFrom($this->advert['final_date'])->format('d/m/Y'); ?></span>
          <?php } else { ?>
            <span style="font-weight: 500;" class="terminele">TERMINÉ !</span>
          <?php }; ?>
          <span class="enchere">Meilleure enchère : </span><span class="enchereNB"><?= (int)$this->advert['actual_price']; ?> €</span>
        </p>

        <!-- Image Véhicule -->
        <img class="imgVehicle" src="<?= $this->advert['picture']; ?>" alt="">

        <!-- Description -->
        <p class="description"><?= $this->advert['description']; ?></p>

        <!-- DIV container -->
        <div class="divContainerDescription">
          <!-- Description ++ DIV -->
          <div class="descriptionContainer">
            <p>
              <span class="firstD">PUISSANCE :</span>
              <span class="secondD"><?= $this->advert['power']; ?>cv</span>
            </p>
            <p>
              <span class="firstD">NOMBRE DE KILOMETRE :</span>
              <span class="secondD"><?= $this->advert['vehicle_km']; ?>km</span>
            </p>
            <p>
              <span class="firstD">DATE D'IMMATRICULATION :</span>
              <span class="secondD"><?= $this->createDateFrom($this->advert['vehicle_year'])->format('d/m/Y'); ?></span>
            </p>
            <p>
              <span class="firstD">PROPRIETAIRE :</span>
              <span class="secondD"><?= ucfirst($this->advert['ownerfn']); ?> <?= ucfirst($this->advert['ownerln']); ?></span>
            </p>
          </div>

          <!-- Description -- DIV -->
          <div class="descriptionContainer">
            <p>
              <span class="firstD">CONTROLE TECHNIQUE :</span>
              <span class="secondD">Oui</span>
            </p>
            <p>
              <span class="firstD">CARTE GRISE :</span>
              <span class="secondD">Française</span>
            </p>
            <p>
              <span class="firstD">VENDEUR :</span>
              <span class="secondD">Particulier</span>
            </p>
            <p>
              <span class="firstD">PRIX DE RESERVE :</span>
              <span class="secondD">Oui</span>
            </p>
          </div>

          <!-- Encher DIV -->
          <?php if ($this->checkDate()) { //! annonce non terminé 
          ?>
            <?php if ($_SESSION['is_connected'] ?? false) { //? utilisateur connecté 
            ?>
              <div class="descriptionContainer encherirContainer">
                <h3 class="titreE">Enchérir</h3>
                <form action="annonce" method="post">
                  <label for="price">Montant</label>
                  <input type="number" name="new_price" id="price">
                  <input type="hidden" name="id" value="<?= $this->advert['id']; ?>">
                  <input type="hidden" name="actual_price" value="<?= $this->advert['actual_price']; ?>">
                  <button type="submit">Valider</button>
                </form>
              </div>
            <?php } else { //? utilisateur NON connecté 
            ?>
              <div class="descriptionContainer encherirContainer notConnected">
                <h3 class="titreE">Enchérir</h3>
                <p class="notConnectedP">Connectez-vous pour enchèrir</p>
                <br /><a class="notConnectedA" href="login">Connexion</a>
              </div>
            <?php };
          } elseif ($this->bidder['bidderln'] ?? false) { //! annonce terminé sans Bidder 
            ?>
            <div class="descriptionContainer encherirContainer notConnected">
              <h3 class="titreE">Remporté par :</h3>
              <p style="font-size:1.1rem" class="notConnectedP"><?= ucfirst($this->bidder['bidderln']); ?> <?= $this->bidder['bidderfn']; ?></p>
            </div>

          <?php } else { //!annonce terminé avec Bidder 
          ?>
            <div class="descriptionContainer encherirContainer notConnected">
              <h3 class="titreE">Remporté par :</h3>
              <p style="font-size:1.1rem" class="notConnectedP"> Aucune personne</p>
            </div>
          <?php }; ?>
        </div>
      </div>

      <?php
        //var_dump($this->bidder);
      ; ?>
      <?php include __DIR__ . "/Component/footer.php"; ?>
    </body>

    </html>

<?php
  }
}