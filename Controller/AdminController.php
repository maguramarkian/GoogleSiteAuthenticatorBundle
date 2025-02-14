<?php

namespace Happyr\GoogleSiteAuthenticatorBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A controller made for administrators to authenticate and revoke access to google.
 */
class AdminController extends Controller
{
    const SESSION_KEY = 'google_token_name';
    
    public function indexAction()
    {
        $clientProvider = $this->get('happyr.google_site_authenticator.client_provider');
        $tokenConfig = $this->get('happyr.google_site_authenticator.token_config');
        $tokenNames = $tokenConfig->getAllKeys();

        $tokens = [];
        foreach ($tokenNames as $tokenName) {
            $tokens[$tokenName] = $clientProvider->isTokenValid($tokenName);
        }

        return $this->render('HappyrGoogleSiteAuthenticatorBundle:admin:index.html.twig', [
            'tokens' => $tokens,
        ]);
    }

    /**
     * This action starts the authentication.
     *
     * @param Request $request
     * @param $name
     *
     * @return Response
     */
    public function authenticateAction(Request $request, $name)
    {
        /* @var \Google_Client $client */
        $clientProvider = $this->get('happyr.google_site_authenticator.client_provider');
        $client = $clientProvider->getClient($name);

        // This will allow us to get refresh the token
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');

        $request->getSession()->set(self::SESSION_KEY, $name);

        return $this->redirect($client->createAuthUrl());
    }

    /**
     * This action revokes the authentication token. This make sure the token can not be used on any other site.
     *
     * @param Request $request
     * @param $name
     *
     * @return Response
     */
    public function revokeAction($name)
    {
        /* @var \Google_Client $client */
        $clientProvider = $this->get('happyr.google_site_authenticator.client_provider');
        $client = $clientProvider->getClient($name);

        $client->revokeToken();
        $clientProvider->setAccessToken(null, $name);

        $this->get('session')->getFlashbag()->add('msg', 'Token was revoked.');

        return $this->redirect($this->generateUrl('happyr.google_site_authenticator.index'));
    }

    /**
     * This action removes the authentication token form the storage.
     *
     * @param Request $request
     * @param $name
     *
     * @return Response
     */
    public function removeAction($name)
    {
        /* @var \Google_Client $client */
        $clientProvider = $this->get('happyr.google_site_authenticator.client_provider');
        $clientProvider->setAccessToken(null, $name);

        $this->get('session')->getFlashbag()->add('msg', 'Token was removed.');

        return $this->redirect($this->generateUrl('happyr.google_site_authenticator.index'));
    }

    /**
     * This action is used when the user has authenticated with google.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function returnAction(Request $request)
    {
        $name = $request->getSession()->get(self::SESSION_KEY, null);

        /* @var \Google_Client $client */
        $clientProvider = $this->get('happyr.google_site_authenticator.client_provider');
        $client = $clientProvider->getClient($name);

        $flashBag = $this->get('session')->getFlashbag();
        if ($request->query->has('code')) {
            try {
                $client->authenticate($request->query->get('code'));
                $clientProvider->setAccessToken($client->getAccessToken(), $name);

                $flashBag->add('msg', 'Successfully authenticated!');
            } catch (\Google_Auth_Exception $e) {
                $flashBag->add('error', $e->getMessage());
            }
        } else {
            $flashBag->add('error', 'Authentication aborted.');
        }

        return $this->redirect($this->generateUrl('happyr.google_site_authenticator.index'));
    }
}
