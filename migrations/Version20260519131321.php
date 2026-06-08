<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519131321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bien_images ADD CONSTRAINT FK_1918415ABD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE biens ADD CONSTRAINT FK_1F9004DD7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE biens ADD CONSTRAINT FK_1F9004DD3414710B FOREIGN KEY (agent_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B072882FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transactions (id)');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288BD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE expenses ADD CONSTRAINT FK_2496F35BBD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
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
        $this->addSql('ALTER TABLE user ADD reset_token VARCHAR(255) DEFAULT NULL, ADD reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE favorites ADD CONSTRAINT FK_E46960F5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favorites ADD CONSTRAINT FK_E46960F5BD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE visites ADD CONSTRAINT FK_470D3983BD95B80F FOREIGN KEY (bien_id) REFERENCES biens (id)');
        $this->addSql('ALTER TABLE visites ADD CONSTRAINT FK_470D398319EB6921 FOREIGN KEY (client_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE biens DROP FOREIGN KEY FK_1F9004DD7E3C61F9');
        $this->addSql('ALTER TABLE biens DROP FOREIGN KEY FK_1F9004DD3414710B');
        $this->addSql('ALTER TABLE bien_images DROP FOREIGN KEY FK_1918415ABD95B80F');
        $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B072882FC0CB0F');
        $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B07288BD95B80F');
        $this->addSql('ALTER TABLE documents DROP FOREIGN KEY FK_A2B07288A76ED395');
        $this->addSql('ALTER TABLE expenses DROP FOREIGN KEY FK_2496F35BBD95B80F');
        $this->addSql('ALTER TABLE favorites DROP FOREIGN KEY FK_E46960F5A76ED395');
        $this->addSql('ALTER TABLE favorites DROP FOREIGN KEY FK_E46960F5BD95B80F');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3A76ED395');
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
        $this->addSql('ALTER TABLE `user` DROP reset_token, DROP reset_token_expires_at');
        $this->addSql('ALTER TABLE visites DROP FOREIGN KEY FK_470D3983BD95B80F');
        $this->addSql('ALTER TABLE visites DROP FOREIGN KEY FK_470D398319EB6921');
    }
}
