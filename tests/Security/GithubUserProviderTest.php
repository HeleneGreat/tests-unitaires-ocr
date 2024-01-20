<?php
namespace App\Tests\Security;

use App\Entity\User;
use App\Security\GithubUserProvider;
use GuzzleHttp\Client;
use JMS\Serializer\Serializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GithubUserProviderTest extends TestCase
{

        private MockObject | Client | null $client;
        private MockObject | Serializer | null $serializer;
        private MockObject | StreamInterface | null $streamedResponse;
        private MockObject | ResponseInterface | null $response;

        public function setUp(): void 
        {
                $this->client = $this->getMockBuilder('GuzzleHttp\Client')
                        ->disableOriginalConstructor()
                        ->setMethods(['get'])
                        ->getMock();
                $this->serializer = $this->getMockBuilder('JMS\Serializer\SerializerInterface')
                        ->disableOriginalConstructor()
                        ->getMock();
                $this->streamedResponse = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
                        ->getMock();
                $this->response = $this->getMockBuilder('Psr\Http\Message\ResponseInterface')
                        ->getMock();

        }

        public function tearDown() : void
        {
                $this->client = null;
                $this->serializer = null;
                $this->streamedResponse = null;
                $this->response = null;
        }

    // Test 1 : a User is returned
    public function testLoadUserByUsernameReturningAUser()
   {
        $this->client->expects($this->once()) // Nous nous attendons à ce que la méthode get soit appelée une fois
                ->method('get')
                ->willReturn($this->response);

        $this->response->expects($this->once())
                ->method('getBody')
                ->willReturn($this->streamedResponse);

        $this->streamedResponse->expects($this->once()) // Nous nous attendons à ce que la méthode getBody soit appelée une fois
                ->method('getContents')
                ->willReturn('foo');

        $userData = 
        [
            'login' => 'a login', 
            'name' => 'user name', 
            'email' => 'adress@mail.com', 
            'avatar_url' => 'url to the avatar', 
            'html_url' => 'url to profile'
        ];
        $this->serializer->expects($this->once()) // Nous nous attendons à ce que la méthode deserialize soit appelée une fois
                ->method('deserialize')
                ->willReturn($userData);

        $githubUserProvider = new GithubUserProvider($this->client, $this->serializer);
        $user = $githubUserProvider->loadUserByUsername('an-access-token');

        $expectedUser = new User($userData['login'], $userData['name'], $userData['email'], $userData['avatar_url'], $userData['html_url']);
        $this->assertEquals($expectedUser, $user);
        $this->assertEquals('App\Entity\User', get_class($user));

   }


   // Test 2 : an exception is thrown
   public function testLoadUserByUsernameThrowingException()
   {
        $this->client->expects($this->once()) // Nous nous attendons à ce que la méthode get soit appelée une fois
                ->method('get')
                ->willReturn($this->response);

        $this->response->expects($this->once())
                ->method('getBody')
                ->willReturn($this->streamedResponse);

        $this->streamedResponse->expects($this->once()) // Nous nous attendons à ce que la méthode getBody soit appelée une fois
                ->method('getContents')
                ->willReturn('foo');

        $this->serializer->expects($this->once()) // Nous nous attendons à ce que la méthode deserialize soit appelée une fois
                ->method('deserialize')
                ->willReturn([]); // Le stub du serializer retourne un tableau vide = aucun user

        $this->expectException('LogicException');
        $githubUserProvider = new GithubUserProvider($this->client, $this->serializer);
        $githubUserProvider->loadUserByUsername('an-access-token');
   }


   
}