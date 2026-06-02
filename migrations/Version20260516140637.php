<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260516140637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bien_images (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, is_main TINYINT DEFAULT 0 NOT NULL, bien_id INT NOT NULL, INDEX IDX_1918415ABD95B80F (bien_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE biens (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, surface NUMERIC(10, 2) NOT NULL, prix NUMERIC(15, 2) NOT NULL, nb_pieces INT DEFAULT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, latitude NUMERIC(10, 7) DEFAULT NULL, longitude NUMERIC(10, 7) DEFAULT NULL, statut VARCHAR(20) NOT NULL, nature VARCHAR(20) NOT NULL, vues INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, owner_id INT NOT NULL, agent_id INT DEFAULT NULL, INDEX IDX_1F9004DD7E3C61F9 (owner_id), INDEX IDX_1F9004DD3414710B (agent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE documents (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, path VARCHAR(255) NOT NULL, type VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL, transaction_id INT DEFAULT NULL, bien_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_A2B072882FC0CB0F (transaction_id), INDEX IDX_A2B07288BD95B80F (bien_id), INDEX IDX_A2B07288A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE expenses (id INT AUTO_INCREMENT NOT NULL, description LONGTEXT NOT NULL, amount NUMERIC(15, 2) NOT NULL, date DATE NOT NULL, created_at DATETIME NOT NULL, bien_id INT NOT NULL, INDEX IDX_2496F35BBD95B80F (bien_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE paiements_loyer (id INT AUTO_INCREMENT NOT NULL, montant_loyer NUMERIC(15, 2) NOT NULL, commission_pourcentage NUMERIC(5, 2) NOT NULL, commission_montant NUMERIC(15, 2) NOT NULL, date_echeance DATE NOT NULL, date_paiement DATE DEFAULT NULL, statut VARCHAR(20) NOT NULL, commentaire LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, transaction_id INT NOT NULL, bien_id INT NOT NULL, locataire_id INT NOT NULL, agent_id INT NOT NULL, INDEX IDX_B817C4D2FC0CB0F (transaction_id), INDEX IDX_B817C4DBD95B80F (bien_id), INDEX IDX_B817C4DD8A38199 (locataire_id), INDEX IDX_B817C4D3414710B (agent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE price_requests (id INT AUTO_INCREMENT NOT NULL, current_price NUMERIC(15, 2) NOT NULL, suggested_price NUMERIC(15, 2) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, bien_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_1933ED46BD95B80F (bien_id), INDEX IDX_1933ED46A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE settings (id INT AUTO_INCREMENT NOT NULL, `key` VARCHAR(255) NOT NULL, value LONGTEXT DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, group_name VARCHAR(50) DEFAULT NULL, UNIQUE INDEX UNIQ_E545A0C58A90ABA9 (`key`), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tasks (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, is_completed TINYINT DEFAULT 0 NOT NULL, due_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_50586597A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transactions (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, montant NUMERIC(15, 2) NOT NULL, statut VARCHAR(20) NOT NULL, commission_pourcentage NUMERIC(5, 2) DEFAULT NULL, commission_montant NUMERIC(15, 2) DEFAULT NULL, date_transaction DATE NOT NULL, date_fin_occupation DATE DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, is_archived TINYINT DEFAULT 0 NOT NULL, client_signed TINYINT DEFAULT 0 NOT NULL, client_signed_at DATETIME DEFAULT NULL, owner_signed TINYINT DEFAULT 0 NOT NULL, owner_signed_at DATETIME DEFAULT NULL, agency_signed TINYINT DEFAULT 0 NOT NULL, agency_signed_at DATETIME DEFAULT NULL, signature_ip VARCHAR(45) DEFAULT NULL, client_signature_image LONGTEXT DEFAULT NULL, owner_signature_image LONGTEXT DEFAULT NULL, agency_signature_image LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, bien_id INT NOT NULL, client_id INT NOT NULL, agent_id INT NOT NULL, visite_id INT DEFAULT NULL, INDEX IDX_EAA81A4CBD95B80F (bien_id), INDEX IDX_EAA81A4C19EB6921 (client_id), INDEX IDX_EAA81A4C3414710B (agent_id), INDEX IDX_EAA81A4CC1C5DC59 (visite_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, photo_url VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE favorites (user_id INT NOT NULL, bien_id INT NOT NULL, INDEX IDX_E46960F5A76ED395 (user_id), INDEX IDX_E46960F5BD95B80F (bien_id), PRIMARY KEY (user_id, bien_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE visites (id INT AUTO_INCREMENT NOT NULL, date_visite DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, commentaire LONGTEXT DEFAULT NULL, feedback_agent LONGTEXT DEFAULT NULL, interested TINYINT DEFAULT 0 NOT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, bien_id INT NOT NULL, client_id INT NOT NULL, INDEX IDX_470D3983BD95B80F (bien_id), INDEX IDX_470D398319EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bien_images ADD CONSTRAINT FK_1918415ABD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE biens ADD CONSTRAINT FK_1F9004DD7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE biens ADD CONSTRAINT FK_1F9004DD3414710B FOREIGN KEY (agent_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B072882FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transactions (id)');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288BD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE expenses ADD CONSTRAINT FK_2496F35BBD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE paiements_loyer ADD CONSTRAINT FK_B817C4D2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transactions (id)');
        $this->addSql('ALTER TABLE paiements_loyer ADD CONSTRAINT FK_B817C4DBD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE paiements_loyer ADD CONSTRAINT FK_B817C4DD8A38199 FOREIGN KEY (locataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE paiements_loyer ADD CONSTRAINT FK_B817C4D3414710B FOREIGN KEY (agent_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE price_requests ADD CONSTRAINT FK_1933ED46BD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE price_requests ADD CONSTRAINT FK_1933ED46A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4CBD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C19EB6921 FOREIGN KEY (client_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C3414710B FOREIGN KEY (agent_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4CC1C5DC59 FOREIGN KEY (visite_id) REFERENCES visites (id)');
        $this->addSql('ALTER TABLE favorites ADD CONSTRAINT FK_E46960F5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favorites ADD CONSTRAINT FK_E46960F5BD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE visites ADD CONSTRAINT FK_470D3983BD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE visites ADD CONSTRAINT FK_470D398319EB6921 FOREIGN KEY (client_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bien_images DROP FOREIGN KEY FK_1918415ABD95B80F');
        $this->addSql('ALTER TABLE biens DROP FOREIGN KEY FK_1F9004DD7E3C61F9');
        $this->addSql('ALTER TABLE biens DROP FOREIGN KEY FK_1F9004DD3414710B');
        $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B072882FC0CB0F');
        $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B07288BD95B80F');
        $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B07288A76ED395');
        $this->addSql('ALTER TABLE expenses DROP FOREIGN KEY FK_2496F35BBD95B80F');
        $this->addSql('ALTER TABLE paiements_loyer DROP FOREIGN KEY FK_B817C4D2FC0CB0F');
        $this->addSql('ALTER TABLE paiements_loyer DROP FOREIGN KEY FK_B817C4DBD95B80F');
        $this->addSql('ALTER TABLE paiements_loyer DROP FOREIGN KEY FK_B817C4DD8A38199');
        $this->addSql('ALTER TABLE paiements_loyer DROP FOREIGN KEY FK_B817C4D3414710B');
        $this->addSql('ALTER TABLE price_requests DROP FOREIGN KEY FK_1933ED46BD95B80F');
        $this->addSql('ALTER TABLE price_requests DROP FOREIGN KEY FK_1933ED46A76ED395');
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597A76ED395');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4CBD95B80F');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C19EB6921');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C3414710B');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4CC1C5DC59');
        $this->addSql('ALTER TABLE favorites DROP FOREIGN KEY FK_E46960F5A76ED395');
        $this->addSql('ALTER TABLE favorites DROP FOREIGN KEY FK_E46960F5BD95B80F');
        $this->addSql('ALTER TABLE visites DROP FOREIGN KEY FK_470D3983BD95B80F');
        $this->addSql('ALTER TABLE visites DROP FOREIGN KEY FK_470D398319EB6921');
        $this->addSql('DROP TABLE bien_images');
        $this->addSql('DROP TABLE biens');
        $this->addSql('DROP TABLE documents');
        $this->addSql('DROP TABLE expenses');
        $this->addSql('DROP TABLE paiements_loyer');
        $this->addSql('DROP TABLE price_requests');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE tasks');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE favorites');
        $this->addSql('DROP TABLE visites');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
