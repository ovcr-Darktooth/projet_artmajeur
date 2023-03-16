<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MonController extends AbstractController
{

    
    private function sauvegarderFormulaire(Request $rqt) {
        $nom = $rqt->request->get('nom');
        $prenom = $rqt->request->get('prenom');
        $email = $rqt->request->get('email');
        $demande = $rqt->request->get('demande');
        $entree = array();
        $entree['nom'] = $nom;
        $entree['prenom'] = $prenom;
        $entree['demande'] = $demande;

        $chemin = $this->getParameter('kernel.project_dir').'/private/formulaire/'. $email . '.json';


        //Si l'utilisateur a déja fait un formulaire avec le même email, on l'ajoute a ces demandes
        if (file_exists($chemin)) {
            $data = file_get_contents($chemin);
            $data = json_decode($data, true);
            $data[count($data)] = $entree;
            $json = json_encode($data);
            
            file_put_contents($chemin, $json);    
        } else { // On initialise les demandes pour l'email
            $data = array();
            $data[0] = $entree;
            $json = json_encode($data);

            file_put_contents($chemin, $json);    
        }
    }


    #[Route('/premiere/page', name: 'app_premiere_page')]
    public function premierePage(): Response
    {
        return $this->render('premiere_page/index.html.twig', [
            'controller_name' => 'MonController',
        ]);
    }


    #[Route('/form', name: 'app_formulaire')]
    public function formulaire(Request $rqt): Response
    {

        $info = '';

        //Si j'ai toutes les informations dans le formulaire renvoyé
        if ($rqt->request->get('email') != "" && $rqt->request->get('nom') != "" && $rqt->request->get('prenom') != "" && $rqt->request->get('demande') != "") {

            //Je sauvegarde la demande
            $this->sauvegarderFormulaire($rqt);

            $info = 'ok';

        } else if ($rqt->request->count() > 0) {
            $info = 'erreur';
        } 

    
        return $this->render('formulaire/index.html.twig', [
            'controller_name' => 'MonController',
            'info' => $info,
        ]);
    }

    #[Route('/admin', name: 'app_administration')]
    public function administration(Request $rqt): Response
    {

        $chemin = $this->getParameter('kernel.project_dir').'/private/formulaire/';

        //Changement du status de traitement d'une demande
        if ($rqt->request->get('email')!= "" && $rqt->request->get('index')!= "") {
            if ($rqt->request->get('status')!= "" && $rqt->request->get('status') == "on") {
                $status = "1";
            } else {
                $status = "0";
            }
            $index = $rqt->request->get('index');
            $data = file_get_contents($chemin. $rqt->request->get('email'). '.json');
            $data = json_decode($data, true);
            $data[$index]["status"] = $status;
            $json = json_encode($data);
            file_put_contents($chemin. $rqt->request->get('email'). '.json', $json);
        }

        
        //Chargement des utilisateurs
        $utilisateurs = array();
        //Vérification du chemin
        if (is_dir($chemin)) {
            //Accès au dossier
            if ($dossier = opendir($chemin)) {
                //Je parcours les emails enregistrés
                while (false !== ($fichier = readdir($dossier))) {
                    //En excluant les . et ..
                    if ($fichier != "." && $fichier != "..") {
                        $index = substr($fichier,0,-5);
                        $utilisateurs[$index] =  json_decode(file_get_contents($chemin . $fichier),true);
                        
                        //Initialisation du status aux emails principalement non traitées
                        foreach($utilisateurs[$index] as $key=>$une_demande) {
                            if (!isset($une_demande["status"])) {
                                $utilisateurs[$index][$key]["status"] = 0;
                            }
                        }
                    }
                }
                closedir($dossier);
            }
        }

        return $this->render('administrateur/index.html.twig', [
            'controller_name' => 'MonController',
            'utilisateurs' => $utilisateurs,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logoff(): Response
    {
        return $this->redirectToRoute('app_formulaire');
    }
    
}
