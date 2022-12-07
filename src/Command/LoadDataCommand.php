<?php

namespace App\Command;

use App\Entity\Post;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[AsCommand(
    name: 'app:load-data',
    description: 'Loads data from JSONPlaceholder'
)]
class LoadDataCommand extends Command
{
    const BASE_URL = 'https://jsonplaceholder.typicode.com';

    private $em;

    public function __construct(ManagerRegistry $em)
    {
        parent::__construct();
        $this->em = $em->getManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->getUsers();

        if (!$users) {
            $io->error('Error with loading users');
            return Command::FAILURE;
        }

        $posts = $this->getPosts();

        if (!$posts) {
            $io->error('Error with loading posts');
            return Command::FAILURE;
        }

        $formatted_users = $this->formatUsers($users);

        for ($i = 0; $i < count($posts); $i++) {
            if (!array_key_exists($posts[$i]['userId'], $formatted_users)) {
                $io->warning('Unable to find post\'s #' . $posts[$i]['id'] . ' author with ID: ' . $posts[$i]['userId']);
                continue;
            }

            $post = new Post;
            $post->setTitle($posts[$i]['title']);
            $post->setBody($posts[$i]['body']);
            $post->setAuthorName($formatted_users[$posts[$i]['userId']]);

            $this->em->persist($post);
        }

        $this->em->flush();

        $io->success('Done');

        return Command::SUCCESS;
    }

    private function getPosts(): array
    {
        $response = $this->request('posts');

        return $response->getStatusCode() === 200 ? $response->toArray() : null;
    }

    private function getUsers(): array
    {
        $response = $this->request('users');

        return $response->getStatusCode() === 200 ? $response->toArray() : null;
    }

    private function request(string $path): ResponseInterface
    {
        return HttpClient::create()->request('GET', self::BASE_URL . '/' . $path);
    }

    private function formatUsers(array $users): array
    {
        $temp = [];

        for ($i = 0; $i < count($users); $i++) {
            $temp[$users[$i]['id']] = $users[$i]['name'];

            // 
            // We also can pass more data but it's not necessary
            // 
            // $temp[$users[$i]['id']] = [
            //     'name' => $users[$i]['name'],
            //     'username' => $users[$i]['username'],
            //     'etc' => '...'
            // ];
        }

        return $temp;
    }
}
