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
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\NotBlank as EmptyConstraint;

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
        if ($data == 'null') $data = false;
        /** @var User $userObject */
        $emptyConstraint = new EmptyConstraint();
        $userObject = $this->getUser();
        $returnData['data'] = '';
        $returnData['error'] = null;
        switch ($fieldName) {
            case 'username':
                $returnData['data'] = $userObject->getUsername();
                try {
                    $userObject->setUsername($data);
                    $this->addToDB($userObject);
                    $returnData['data'] = $userObject->getUsername();
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                break;
            case 'voornaam':
                $returnData['data'] = $userObject->getVoornaam();
                try {
                    $userObject->setVoornaam($data);
                    $this->addToDB($userObject);
                    $returnData['data'] = $userObject->getVoornaam();
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                break;
            case 'achternaam':
                $returnData['data'] = $userObject->getAchternaam();
                try {
                    $userObject->setAchternaam($data);
                    $this->addToDB($userObject);
                    $returnData['data'] = $userObject->getAchternaam();
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                break;
            case 'email':
                $returnData['data'] = $userObject->getEmail();
                $errors = $this->get('validator')->validate(
                    $data,
                    $emptyConstraint
                );
                if (count($errors) == 0) {
                    $emailConstraint = new EmailConstraint();
                    $errors = $this->get('validator')->validate(
                        $data,
                        $emailConstraint
                    );
                    if (count($errors) == 0) {
                        try {
                            $userObject->setEmail($data);
                            $this->addToDB($userObject);
                            $returnData['data'] = $userObject->getEmail();
                        } catch (\Exception $e) {
                            $returnData['error'] = $e->getMessage();
                        }
                    } else {
                        foreach ($errors as $error) {
                            $returnData['error'] .= $error->getMessage() . ' ';
                        }
                    }
                } else {
                    foreach ($errors as $error) {
                        $returnData['error'] .= $error->getMessage() . ' ';
                    }
                }
                break;
            case 'verantwoordelijkheid':
                $returnData['data'] = $userObject->getVerantwoordelijkheid();
                try {
                    $userObject->setVerantwoordelijkheid($data);
                    $this->addToDB($userObject);
                    $returnData['data'] = $userObject->getVerantwoordelijkheid();
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                break;
            default:
                $returnData['error'] = 'An unknown error occurred, please contact webmaster@haagsebosancup.nl';
        }
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
