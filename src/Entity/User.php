<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields={"Username"}, message="Konto o podanej nazwie już istnieje")
 */
class User implements UserInterface, \Serializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\Length(min="4")
     */
    private $Username;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(min="6")
     */
    private $Password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Token;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->Username;
    }

    public function setUsername(string $Username): self
    {
        $this->Username = $Username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->Password;
    }

    public function setPassword(string $Password): self
    {
        $this->Password = $Password;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->Token;
    }

    public function setToken(string $Token): self
    {
        $this->Token = $Token;

        return $this;
    }

    private $roles; 
    public function getRoles() {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles($roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getSalt() {}

    public function eraseCredentials() {}

    public function serialize() {
        return serialize([
            $this->id,
            $this->Username,
            $this->Password,
            $this->Token
        ]);
    }

    public function unserialize($string) {
        list(
            $this->id,
            $this->Username,
            $this->Password,
            $this->Token
        ) = unserialize($string, ['allowed_classes' => false]);
    }
}
