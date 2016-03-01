<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Instellingen;
use AppBundle\Entity\User;
use AppBundle\Entity\Voorinschrijving;
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
    public function getOrganisatiePage($page, $successMessage = false)
    {
        $this->setBasicPageData('Organisatie');
        switch ($page) {
            case 'Home':
                return $this->getOrganisatieHomePage();
            case 'To-do lijst':
                return $this->getOrganisatieHomePage();
            case 'Instellingen':
                return $this->getOrganisatieInstellingenPage($successMessage);
            case 'Mails':
                return $this->getOrganisatieHomePage();
            case 'Inschrijvingen':
                return $this->getOrganisatieHomePage();
            case 'Juryzaken':
                return $this->getOrganisatieHomePage();
            case 'Financieel':
                return $this->getOrganisatieHomePage();
            case 'Mijn gegevens':
                return $this->getOrganisatieGegevensPage($successMessage);
        }
    }

    private function getGegevens()
    {
        $userObject = $this->getUser();
        return $userObject->getAll();
    }

    private function getOrganisatieGegevensPage($successMessage = false)
    {
        $gegevens = $this->getGegevens();
        return $this->render('organisatie/organisatieGegevens.html.twig', array(
            'menuItems' => $this->menuItems,
            'gegevens' => $gegevens,
            'successMessage' => $successMessage,
        ));
    }

    /**
     * @Route("/organisatie/editInstellingen/{fieldName}/{data}/", name="editInstellingen", options={"expose"=true})
     * @Method("GET")
     */
    public function editInstellingen($fieldName, $data)
    {
        $fieldName = str_replace('_', ' ', $fieldName);
        $returnData['error'] = null;
        $result = $this->getOrganisatieInstellingen($fieldName);
        $returnData['data'] = $result[$fieldName];
        if ($data == 'null') {
            return new JsonResponse($returnData);
        }
        $instellingen = new Instellingen();
        switch ($fieldName) {
            case self::MAX_AANTAL_TURNSTERS:
                try {
                    $instellingen->setInstelling($fieldName);
                    $instellingen->setGewijzigd(new \DateTime('now'));
                    $instellingen->setAantal($data);
                    $this->addToDB($instellingen);
                    $result = $this->getOrganisatieInstellingen($fieldName);
                    $returnData['data'] = $result[$fieldName];
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
                break;
            default:
                try {
                    $instellingen->setInstelling($fieldName);
                    $instellingen->setGewijzigd(new \DateTime('now'));
                    $instellingen->setDatum(new \DateTime($data));
                    $this->addToDB($instellingen);
                    $result = $this->getOrganisatieInstellingen($fieldName);
                    $returnData['data'] = $result[$fieldName];
                } catch (\Exception $e) {
                    $returnData['error'] = $e->getMessage();
                }
        }
        return new JsonResponse($returnData);
    }

    /**
     * @Route("/organisatie/{page}/genereerVoorinschrijving/", name="genereerVoorinschrijving")
     * @Method({"GET", "POST"})
     */
    public function genereerVoorinschrijving(Request $request, $page)
    {
        if ($request->request->get('email')) {
            $this->createVoorinschrijvingToken($request->request->get('email'));
            return $this->getOrganisatiePage($page, $successMessage = 'voorinschrijvijngslink verstuurd');
        } else {
            $this->setBasicPageData('Organisatie');
            return $this->render('organisatie/genereerVoorinschrijving.html.twig', array(
                'menuItems' => $this->menuItems,
            ));
        }
    }

    private function removeVoorinschrijving($id)
    {
        $result = $this->getDoctrine()
            ->getRepository('AppBundle:Voorinschrijving')
            ->findOneBy(
                array('id' => $id)
            );
        if ($result) {
            $this->removeFromDB($result);
        }
    }

    /**
     * @Route("/organisatie/{page}/removeVoorinschrijving/{id}", name="removeVoorinschrijving")
     * @Method({"GET"})
     */
    public function removeVoorinschrijvingsPage($page, $id)
    {
        $this->removeVoorinschrijving($id);
        return $this->getOrganisatiePage($page, $successMessage = 'voorinschrijvijngslink verwijderd');
    }

    private function refreshVoorinschrijving($id)
    {
        /** @var Voorinschrijving $result */
        $result = $this->getDoctrine()
            ->getRepository('AppBundle:Voorinschrijving')
            ->findOneBy(
                array('id' => $id)
            );
        if ($result) {
            $this->createVoorinschrijvingToken($result->getTokenSentTo(), $result);
        }
    }

    /**
     * @Route("/organisatie/{page}/refreshVoorinschrijving/{id}", name="refreshVoorinschrijving")
     * @Method({"GET"})
     */
    public function refreshVoorinschrijvingsPage($page, $id)
    {
        $this->refreshVoorinschrijving($id);
        return $this->getOrganisatiePage($page, $successMessage = 'voorinschrijvijngslink opnieuw verstuurd');
    }

    private function getVoorinschrijvingen()
    {
        /** @var Voorinschrijving[] $results */
        $results = $this->getDoctrine()
            ->getRepository('AppBundle:Voorinschrijving')
            ->findBy(
                [],
                ['createdAt' => 'DESC']
            );
        $voorinschrijvingen = [];
        foreach ($results as $result) {
            $voorinschrijvingen[] = $result->getAll();
        }
        return $voorinschrijvingen;
    }

    private function getOrganisatieInstellingenPage($successMessage = false)
    {
        $instellingen = $this->getOrganisatieInstellingen();
        $voorinschrijvingen = $this->getVoorinschrijvingen();
        return $this->render('organisatie/organisatieInstellingen.html.twig', array(
            'menuItems' => $this->menuItems,
            'instellingen' => $instellingen,
            'voorinschrijvingen' => $voorinschrijvingen,
            'successMessage' => $successMessage,
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
        if ($data == 'null') {
            $data = false;
        }
        /** @var User $userObject */
        $emptyConstraint = new EmptyConstraint();
        $userObject = $this->getUser();
        $returnData['data'] = '';
        $returnData['error'] = null;
        switch ($fieldName) {
            case 'voornaam':
                $returnData['data'] = $userObject->getVoornaam();
                $errors = $this->get('validator')->validate(
                    $data,
                    $emptyConstraint
                );
                if (count($errors) == 0) {
                    try {
                        $userObject->setVoornaam($data);
                        $this->addToDB($userObject);
                        $returnData['data'] = $userObject->getVoornaam();
                    } catch (\Exception $e) {
                        $returnData['error'] = $e->getMessage();
                    }
                } else {
                    foreach ($errors as $error) {
                        $returnData['error'] .= $error->getMessage() . ' ';
                    }
                }
                break;
            case 'achternaam':
                $returnData['data'] = $userObject->getAchternaam();
                $errors = $this->get('validator')->validate(
                    $data,
                    $emptyConstraint
                );
                if (count($errors) == 0) {
                    try {
                        $userObject->setAchternaam($data);
                        $this->addToDB($userObject);
                        $returnData['data'] = $userObject->getAchternaam();
                    } catch (\Exception $e) {
                        $returnData['error'] = $e->getMessage();
                    }
                } else {
                    foreach ($errors as $error) {
                        $returnData['error'] .= $error->getMessage() . ' ';
                    }
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
        return new JsonResponse($returnData);
    }

    /**
     * @Security("has_role('ROLE_ORGANISATIE')")
     * @Route("/organisatie/{page}/editPassword/", name="editPassword")
     * @Method({"GET", "POST"})
     */
    public function editPassword(Request $request, $page)
    {
        if ($page == 'Mijn gegevens') {
            $error = false;
            if ($request->getMethod() == 'POST') {
                if ($request->request->get('pass1') != $request->request->get('pass2')) {
                    $this->addFlash(
                        'error',
                        'De wachtwoorden zijn niet gelijk'
                    );
                    $error = true;
                }
                if (strlen($request->request->get('pass1')) < 6) {
                    $this->addFlash(
                        'error',
                        'Het wachtwoord moet minimaal 6 karakters bevatten'
                    );
                    $error = true;
                }
                if (strlen($request->request->get('pass1')) > 20) {
                    $this->addFlash(
                        'error',
                        'Het wachtwoord mag maximaal 20 karakters bevatten'
                    );
                    $error = true;
                }
                if (!($error)) {
                    $userObject = $this->getUser();
                    $password = $request->request->get('pass1');
                    $encoder = $this->container
                        ->get('security.encoder_factory')
                        ->getEncoder($userObject);
                    $userObject->setPassword($encoder->encodePassword($password, $userObject->getSalt()));
                    $this->addToDB($userObject);
                    $this->addFlash(
                        'success',
                        'Het wachtwoord is succesvol gewijzigd'
                    );
                    return $this->redirectToRoute('organisatieGetContent', array(
                        'page' => $page,
                    ));
                }
            }
            $this->setBasicPageData();
            return $this->render('organisatie/editPassword.html.twig', array(
                'menuItems' => $this->menuItems,
            ));
        }
    }

}
