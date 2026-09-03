<?php

namespace App\Command;

use App\Entity\Organization;
use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Cria ou atualiza um usuário administrador no banco de dados',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly OrganizationRepository $organizationRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'E-mail do usuário')
            ->addArgument('password', InputArgument::OPTIONAL, 'Senha do usuário')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Nome completo', 'Administrador')
            ->addOption('org', null, InputOption::VALUE_OPTIONAL, 'Nome da Organização', 'Liga Principal')
            ->addOption('role', null, InputOption::VALUE_OPTIONAL, 'Perfil de acesso', 'ROLE_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email') ?: $io->ask('Digite o e-mail do usuário', 'admin@futebollocal.com');
        $password = $input->getArgument('password') ?: $io->askHidden('Digite a senha do usuário');

        if (empty($password)) {
            $password = 'admin123';
            $io->note('Nenhuma senha informada, usando a padrão: admin123');
        }

        $name = $input->getOption('name');
        $orgName = $input->getOption('org');
        $role = $input->getOption('role');

        // Busca ou cria uma organização para o usuário
        $organization = $this->organizationRepository->findOneBy([]) ?? null;
        if (!$organization) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $orgName)));
            $organization = new Organization($orgName, $slug ?: 'organizacao-principal');
            $organization->setCity('Januária')->setState('MG');
            $this->em->persist($organization);
            $this->em->flush();
            $io->info(sprintf('Organização "%s" criada.', $orgName));
        }

        // Busca ou cria o usuário
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $user = new User($name, $email, $organization);
            $user->setApiToken('token_' . bin2hex(random_bytes(16)));
            $this->em->persist($user);
            $io->info(sprintf('Novo usuário "%s" sendo criado.', $email));
        } else {
            $io->info(sprintf('Usuário "%s" já existe. Atualizando dados...', $email));
        }

        $user->setRoles([$role]);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->em->flush();

        $io->success([
            'Usuário configurado com sucesso!',
            sprintf('E-mail: %s', $email),
            sprintf('Perfil: %s', $role),
            sprintf('Organização: %s', $organization->getName()),
        ]);

        return Command::SUCCESS;
    }
}
