<?php

namespace RedpillLinpro\SearchAnalyzerBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class SearchAnalyzerController extends Controller
{

    public function intelliBarAction($collection, $query = null, $focus = false)
    {
        $sa = $this->get('searchanalyzer_' . $collection);
        $fields = $sa->getFieldDefinitions();
        $field_translations = array();
        $translator = $this->get('translator');
        foreach ($fields as $key => $aliases) {
            $field_translations[$key] = $translator->trans($key);
            if (!in_array($key, $fields[$key])) {
                $fields[$key][] = $key;
            }
        }

        return $this->render('RedpillLinproSearchAnalyzerBundle:SearchAnalyzer:intellibar.html.twig', compact('fields', 'field_translations', 'query', 'focus'));
    }

}

