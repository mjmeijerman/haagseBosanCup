<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Httpfoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception;
use AppBundle\Controller\BaseController;
use Symfony\Component\Validator\Constraints\DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Security("has_role('ROLE_ORGANISATIE')")
 */
class OrganisatieController extends BaseController
{
    /**
     * @Route("/organisatie/{page}/", name="organisatieGetContent", defaults={"page" = "Home"})
     * @Method("GET")
     */
    public function getOrganisatiePage($page)
    {
        $this->setBasicPageData('Organisatie');
        switch ($page) {
            case 'Home':
                return $this->getOrganisatieHomePage();
            case 'To-do lijst':
                return $this->getOrganisatieHomePage();
            case 'Instellingen':
                return $this->getOrganisatieHomePage();
            case 'Mails':
                return $this->getOrganisatieHomePage();
            case 'Inschrijvingen':
                return $this->getOrganisatieHomePage();
            case 'Juryzaken':
                return $this->getOrganisatieHomePage();
            case 'Financieel':
                return $this->getOrganisatieHomePage();
            case 'Mijn gegevens':
                return $this->getOrganisatieGegevensPage();
        }
    }

    private function getGegevens()
    {
        $userObject = $this->getUser();
        return $userObject->getAll();
    }

    private function getOrganisatieGegevensPage()
    {
        $gegevens = $this->getGegevens();
        return $this->render('organisatie/organisatieGegevens.html.twig', array(
            'menuItems' => $this->menuItems,
            'gegevens' => $gegevens,
        ));
    }

    private function getOrganisatieHomePage()
    {
        return $this->render('organisatie/organisatieIndex.html.twig', array(
            'menuItems' => $this->menuItems,
        ));
    }

    /**
     * @Route("/organisatie/edit/{fieldName}/{data}/", name="editGegevens", options={"expose"=true})
     * @Method("GET")
     */
    public function editGegevens($fieldName, $data)
    {
        /** @var User $userObject */
        $userObject = $this->getUser();
        $returnData['data'] = '';
        switch ($fieldName) {
            case 'username':
                try {
                    $userObject->setUsername($data);
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                $returnData['data'] = $userObject->getUsername();
                break;
            case 'voornaam':
                try {
                    $userObject->setVoornaam($data);
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                $returnData['data'] = $userObject->getVoornaam();
                break;
            case 'achternaam':
                try {
                    $userObject->setAchternaam($data);
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                $returnData['data'] = $userObject->getAchternaam();
                break;
            case 'email':
                try {
                    $userObject->setEmail($data);
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                $returnData['data'] = $userObject->getEmail();
                break;
            case 'verantwoordelijkheid':
                try {
                    $userObject->setVerantwoordelijkheid($data);
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                $returnData['data'] = $userObject->getVerantwoordelijkheid();
                break;
            default:
                $returnData['error'] = 'An unknown error occurred, please contact webmaster@haagsebosancup.nl';
        }
        $this->addToDB($userObject);
        $response = new JsonResponse($returnData);
        return $response;
    }

    /**
     * @Route("/organisatie/edit/{fieldName}/", name="removeGegevens", options={"expose"=true})
     * @Method("GET")
     */
    public function removeGegevens($fieldName)
    {
        /** @var User $userObject */
        $userObject = $this->getUser();
        $returnData = 'error';
        switch ($fieldName) {
            case 'username':
                $returnData = $userObject->getUsername();
                break;
            case 'voornaam':
                $returnData = $userObject->getVoornaam();
                break;
            case 'achternaam':
                $returnData = $userObject->getAchternaam();
                break;
            case 'email':
                $returnData = $userObject->getEmail();
                break;
            case 'verantwoordelijkheid':
                $userObject->setVerantwoordelijkheid(null);
                $returnData = $userObject->getVerantwoordelijkheid();
                break;
        }
        $this->addToDB($userObject);
        $response = new Response($returnData);
        return $response;
    }

}
