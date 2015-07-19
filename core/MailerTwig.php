<?php
namespace Bro\core;

require_once 'Mail.php';
require_once 'Mail/smtp.php';
require_once 'Mail/mime.php';

class MailerTwig
{

    /**
     * Singleton instance storage
     * @var MailerTwig
     */
    private static $p_Instance;

    /**
     * "From" mail address
     * @var string
     */
    public $fromAddress = 'noreply@3cam.ru';

    /**
     * "From" user full name
     * @var string
     */
    public $fromFullname = 'Robot';

    /**
     * Twig - template engine
     *
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * Mail settings refer to Mail::Mail_smtp constructor
     *
     * @var array
     */
    public $mailSettings = array(
        'host' => 'localhost'
    );

    /**
     * Mime settings refer to Mail_Mime::Mail_Mime
     *
     * @var array
     */
    public $mimeSettings = array(
        'head_charset' => 'utf-8',
        'text_charset' => 'utf-8',
        'html_charset' => 'utf-8',
    );

    static function getInstance()
    {
        if (!self::$p_Instance) {
            self::$p_Instance = new MailerTwig();
        }
        return self::$p_Instance;
    }

    private function __construct()
    {
        global $twigConfig;
        $loader = new \Twig_Loader_Filesystem($twigConfig['templates']);
        $this->twig = new \Twig_Environment($loader, array('cache' => $twigConfig['cache'], 'auto_reload' => 'True'));
    }

    public function sendMail(
        $toAddress, $toFullname, $subject, $templateName, $templateData, $headers = array(), $images = array(), $attachments = array())
    {
        $mailer = \Mail::factory('smtp', $this->mailSettings);
        $mime = new \Mail_mime($this->mimeSettings);

        // prepare headers
        $localHeaders = $headers;
        $localHeaders['subject'] = $subject;
        if (empty($toFullname)) {
            $localHeaders['To'] = $toAddress;
        } else {
            $localHeaders['To'] = "$toFullname <$toAddress>";
        };

        if (empty($this->fromFullname)) {
            $localHeaders['From'] = $this->fromAddress;
        } else {
            $localHeaders['From'] = $this->fromFullname . ' <' . $this->fromAddress . '>';
        };
        $localHeaders['Content-Type'] = 'text/html; charset=UTF-8';
        $localHeaders['Content-Transfer-Encoding'] = '8bit';

        // prepare HTML
        try {
            $templateEngine = $this->twig->loadTemplate($templateName);
        } catch (\Twig_Error_Syntax $e) {
            // turn the error message into a validation error 
            die("TWIG syntax error: " . $e->getRawMessage() . "<br>\n File: " . $e->getTemplateFile() . " : " . $e->getTemplateLine());
        }
        $htmlPage = $templateEngine->render($templateData);
        $textPage = "Please see this mail in HTML format";

        $mime->setTXTBody($textPage);
        $mime->setHTMLBody($htmlPage);

        foreach ($images as $image) {
            $mime->$mime->addHTMLImage($image['file'], $image['mimeType']);
        }

        foreach ($attachments as $attachment) {
            $mime->addAttachment($attachment['file'], $attachment['mimeType']);
        }

        $mimeBody = $mime->get();
        $mimeHeaders = $mime->headers($localHeaders);

        return $mailer->send($toAddress, $mimeHeaders, $mimeBody);
    }

}

