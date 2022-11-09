<?php

namespace CoreBundle\Command;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\EmailTemplates;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestSendMailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:test-send-mail')
            ->addArgument('to', InputArgument::REQUIRED, 'Email recipient')
            ->addOption('tpl', null, InputOption::VALUE_REQUIRED, 'TemplateId:language, example: "letter_reset_password:en", "letter_reset_password"', 'letter_reset_password:en')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailer = $this->getContainer()->get('core.service.mailer');
        $templateRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository(EmailTemplates::class);

        $templateParts = explode(':', $input->getOption('tpl'));
        $templateId = $templateParts[0];
        $templateLanguage = $templateParts[1] ?? Language::EN;
        /** @var EmailTemplates $template */
        $template = $templateRepository->findOneBy(['identificator' => $templateId, 'language' => $templateLanguage]);

        if (!$template) {
            throw new \LogicException('Template "' .$input->getOption('tpl'). '" not found');
        }

        $marks = [];
        if (preg_match_all('~%([a-z0-9_-]+)%~ui', $template->getEmailContent(), $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $marks[$match[0]] = self::generateMarkValue($match[1]);
            }
        }

        if ($mailer->sendToEmail($templateId, $input->getArgument('to'), $marks, $templateLanguage)) {
            $output->writeln('Message sent successfully:');
        } else {
            $output->writeln('Message has not been sent:');
        }
        $output->writeln('To: '.$input->getArgument('to'));
        $output->writeln('Template: '.$templateId .' '.$templateLanguage);
    }

    private static function generateMarkValue($markName)
    {
        if (stripos($markName, 'name') !== false) {
            $variants = ['John Smith', 'Dante Ivanovich'];

            return $variants[array_rand(['John Smith', 'Dante Ivanovich'])];
        }

        if ($markName === 'link') {
            $variants = ['http://ereferer.com/', 'http://en.ereferer.com/bo/dashboard'];

            return $variants[array_rand($variants)];
        }

        if ($markName === 'baseUrl') {
            return 'http://ereferer.com/';
        }

        if (stripos($markName, 'link') !== false || stripos($markName, 'url') !== false) {
            $variants = ['bo/copywriting/order/list?status=waiting', '/bo/dashboard'];

            return $variants[array_rand($variants)];
        }
    }
}
