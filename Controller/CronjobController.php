<?php


namespace AgentSIB\CrontabBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CronjobController extends Controller
{
    public function listAction (Request $request)
    {


        $cronjobs = $this->get('agentsib_crontab.manager')->getDatabaseCronjobs();

        if ($request->query->has('action') && $request->query->has('rand')) {
            $rand = $request->query->get('rand');
            $cronjob = $this->get('agentsib_crontab.manager')->getCronjobById($request->query->get('id'));
            if ($cronjob && !empty($rand) && $rand == $request->getSession()->get('agentsib_crontab_rand', '')) {
                switch($request->query->get('action')) {
                    case 'enable':
                        $this->get('agentsib_crontab.manager')->enableCronjob($cronjob);
                        break;
                    case 'disable':
                        $this->get('agentsib_crontab.manager')->disableCronjob($cronjob);
                        break;
                    case 'immediately':
                        $this->get('agentsib_crontab.manager')->executeImmediatelyCronjob($cronjob);
                        break;
                }
            }
            return $this->redirect($this->generateUrl('agentsib_cronjob_tasks'));
        }

        $rand = mt_rand(10000000, 90000000);

        $request->getSession()->set('agentsib_crontab_rand', $rand);

        return $this->render('AgentSIBCrontabBundle::list.html.twig', array(
            'cronjobs'  =>  $cronjobs,
            'rand'  =>  $rand
        ));
    }
}