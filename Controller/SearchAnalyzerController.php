<?php

namespace RedpillLinpro\SearchAnalyzerBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class SearchAnalyzerController extends Controller
{

    public function intelliBarAction($collection, $path, $query = null, $focus = false, $all_contexts = null)
    {
        $sa = $this->get('searchanalyzer_' . $collection);
        if ($all_contexts === null) unset($all_contexts);
        $fields = $sa->getFieldDefinitions();
        $field_translations = array();
        $translator = $this->get('translator');
        foreach ($fields as $key => $aliases) {
            $field_translations[$key] = $translator->trans($key);
        }

        return $this->render('RedpillLinproSearchAnalyzerBundle:SearchAnalyzer:intellibar.html.twig', compact('fields', 'collection', 'path', 'field_translations', 'query', 'focus', 'all_contexts'));
    }

}

