<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */
namespace Skadate\Mobile\Controller;

use CONTACTUS_BOL_Department;
use CONTACTUS_BOL_Service;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OW;

class ContactUs extends Base
{

    /**
     * Is plugin active
     *
     * @var bool
     */
    protected $isPluginActive = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->isPluginActive = OW::getPluginManager()->isPluginActive('contactus');
    }

    /**
     * Connect methods
     *
     * @param SilexApplication $app
     * @return mixed
     */
    public function connect(SilexApplication $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->post('/', function (Request $request) use ($app) {
            if ($this->isPluginActive) {
                $data = json_decode($request->getContent(), true);
                $departments = CONTACTUS_BOL_Service::getInstance()->getDepartmentList();
                $contactEmails = [];

                foreach ( $departments as $department )
                {
                    /* @var $contact CONTACTUS_BOL_Department */
                    $contactEmails[$department->id] = $department->email;
                }

                if (!isset($contactEmails[$data['to']]))
                {
                    throw new BadRequestHttpException('Department not found');
                }

                $mail = OW::getMailer()->createMail();
                $mail->addRecipientEmail($contactEmails[$data['to']]);
                $mail->setSender($data['from']);
                $mail->setSenderSuffix(false);
                $mail->setSubject($data['subject']);
                $mail->setTextContent($data['message']);
                $mail->setReplyTo($data['from']);

                OW::getMailer()->addToQueue($mail);

                return $app->json([], 204);
            }

            throw new BadRequestHttpException('Contacts us plugin is not activated');
        });

        $controllers->get('/departments/', function () use ($app) {
            if ($this->isPluginActive) {
                $departments = CONTACTUS_BOL_Service::getInstance()->getDepartmentList();
                $contactEmails = [];

                foreach ( $departments as $department )
                {
                    /* @var $contact CONTACTUS_BOL_Department */
                    $contactEmails[] = [
                        'id' => $department->id,
                        'name' => CONTACTUS_BOL_Service::getInstance()->getDepartmentLabel($department->id)
                    ];
                }

                return $app->json($contactEmails);
            }

            throw new BadRequestHttpException('Contacts us plugin is not activated');
        });

        return $controllers;
    }
}
