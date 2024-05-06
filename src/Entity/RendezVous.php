<?php

namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\RendezVousRepository;
use DateTimeInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RendezVousRepository")
 */

#[ORM\Entity(repositoryClass: RendezVousRepository::class)]
class RendezVous
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private $refRendezVous;

    #[ORM\Column]
    private ?DateTimeInterface $dateRendezVous;

    /**
     * @var \Medecin
     *
     * @ORM\ManyToOne(targetEntity="Medecin")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_medecin", referencedColumnName="id_medecin")
     * })
     */
    #[ORM\ManyToOne(targetEntity: Medecin::class)]
    #[ORM\JoinColumn(name: 'id_medecin', referencedColumnName: 'id_medecin')]
    private ?Medecin $idMedecin;


    #[ORM\ManyToOne(targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'id_personne', referencedColumnName: 'id_personne')]
    private ?Client $idPersonne;

    public function getRefRendezVous(): ?int
    {
        return $this->refRendezVous;
    }

    public function getDateRendezVous(): ?\DateTimeInterface
    {
        return $this->dateRendezVous;
    }

    public function setDateRendezVous(\DateTimeInterface $dateRendezVous): static
    {
        $this->dateRendezVous = $dateRendezVous;

        return $this;
    }

    public function getIdMedecin(): ?Medecin
    {
        return $this->idMedecin;
    }

    public function setIdMedecin(?Medecin $idMedecin): static
    {
        $this->idMedecin = $idMedecin;

        return $this;
    }

    public function getIdPersonne(): ?Client
    {
        return $this->idPersonne;
    }

    public function setIdPersonne(?Client $idPersonne): static
    {
        $this->idPersonne = $idPersonne;

        return $this;
    }


}
