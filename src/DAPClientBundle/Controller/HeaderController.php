<?php
/**
 * File containing the HeaderController class.
 *
 * (c) http://parsonstko.com/
 */

namespace DAPClientBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class HeaderController extends Controller
{
    /**
     * Renders head with logo.
     *
     * @param $showback
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($showback = false)
    {
        try {
            return $this->render(
                'DAPClientBundle:Header:show.html.twig',
                array(
                    'showback' => $showback,
                )
            );
        } catch (\Exception $e) {
            $this->get('dap_client.logger')->error($e->getMessage());

            return new Response();
        }
    }

}
