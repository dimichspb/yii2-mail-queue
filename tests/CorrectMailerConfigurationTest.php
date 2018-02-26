<?php
namespace dimichspb\yii\mailqueue\tests;

class CorrectMailerConfigurationTest extends TestCase
{
    /**
     * @after  CreateComponentTest
     */
    public function testUseFileTransportIsTrue()
    {
        $this->assertTrue($this->app->mailer->getMailer()->useFileTransport);
    }

}