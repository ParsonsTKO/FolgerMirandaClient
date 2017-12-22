<?php
/**
 * File containing the HeadController class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer jdiaz
 */

namespace DAPClientBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class HeadController extends Controller
{
    /**
     * Renders metadata.
     *
     * @param $pageTitle
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function titleAction($viewTitle = '')
    {
        try {
            $headSettings = $this->getParameter('dap_client.head');
            $title = $headSettings['metadata']['title_suffix'];

            if ($viewTitle != '') {
                $title = $viewTitle.' - '.$title;
            }

            return $this->render(
                'DAPClientBundle:Head:title.html.twig',
                array(
                    'title' => $title,
                )
            );
        } catch (\Exception $e) {
            $this->get('dap_client.logger')->error($e->getMessage());

            return new Response();
        }
    }

    /**
     * Renders metadata.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function metadataAction($detailMeta = null)
    {
        try {
            $headSettings = $this->getParameter('dap_client.head');
            $metadata = array();

            //site defaults
            foreach ($headSettings['metadata'] as $metadataIdentifier => $metadataValue) {
                $metadata[$metadataIdentifier] = $metadataValue;
            }

            //detail metadata
            $metadetail = array();
            if (isset($detailMeta)) {
                foreach ($detailMeta as $k => $v) {
                    if (isset($k) && isset($v) && !is_null($k) && !is_null($v)) {
                        //tie in here to filter and process
                        $metadetail[$k] = $v;
                    }
                }
            }

            return $this->render(
                'DAPClientBundle:Head:metadata.html.twig',
                array(
                    'metadata' => $metadata,
                    'detailmeta' => $metadetail
                )
            );
        } catch (\Exception $e) {
            $this->get('dap_client.logger')->error($e->getMessage());

            return new Response();
        }
    }
}
