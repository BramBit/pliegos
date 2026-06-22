# Pliegos 🏛️

> Intelligent semantic search engine for Colombian public tenders powered by RAG architecture.

Pliegos connects companies with government contracting opportunities published in the **SECOP** (Colombia's public procurement system). Instead of manually reviewing hundreds of documents daily, Pliegos understands what your company does and delivers the most relevant tenders directly to your inbox — with an AI-generated executive summary.

---

## ✨ Features

- 🔍 **Semantic search** — finds tenders by meaning, not just keywords
- 🤖 **Conversational AI** — ask questions in natural language about available tenders
- ⚡ **Smart caching** — sectors already indexed are never reprocessed
- 📬 **Automated reports** — AI-generated summaries delivered to your email
- 🔄 **Async processing** — embeddings and reports generated in background jobs
- 🔀 **Strategy pattern** — Ollama in development, OpenAI in production

---

## 🏗️ Architecture

```
User
 │
 ▼
┌─────────────────────────────────────────────┐
│                  Laravel API                 │
│                                             │
│  Auth ──► TenderSearch ──► TenderController │
│                                 │           │
│                         SecopService        │
│                         (fetch + cache)     │
│                                 │           │
│                         Queue Jobs          │
│                    ┌────────────┴──────────┐│
│                    │                       ││
│         GenerateTenderEmbedding   GenerateAndSendReport
│                    │                       ││
│             EmbeddingService         ChatService + ReportMailService
│          (Strategy Pattern)        (Strategy Pattern)
│                    │                       ││
│              pgvector DB              Resend API
└─────────────────────────────────────────────┘
```

---

## 🔄 Full Flow

```
1. User registers/logs in
         │
         ▼
2. Creates a TenderSearch
   (company, sector, budget range)
         │
         ▼
3. Requests tenders for that search
         │
         ├── Sector already indexed? ──YES──► Query pgvector directly
         │
         └── NO ──► Fetch from SECOP API
                          │
                          ▼
                   Store in `tenders` table
                          │
                          ▼
                   Dispatch GenerateTenderEmbedding job (async)
                          │
                          ▼
                   Ollama/OpenAI generates embedding
                          │
                          ▼
                   Store vector in pgvector
                          │
                          ▼
4. User asks a question in natural language
         │
         ▼
   Embed the question ──► Cosine similarity search in pgvector
         │
         ▼
   Top 5 tenders passed as context to LLM
         │
         ▼
   Conversational answer returned
         │
         ▼
5. User requests report
         │
         ▼
   Dispatch GenerateAndSendReport job (async)
         │
         ▼
   LLM generates executive summary
         │
         ▼
   Report saved + email sent via Resend
```

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.3 · Laravel 11 |
| Database | PostgreSQL 17 · pgvector (HNSW index) |
| Queue | Laravel Queue · Database driver |
| AI — Embeddings | Ollama `nomic-embed-text` (dev) · OpenAI `text-embedding-3-small` (prod) |
| AI — Chat | Ollama `llama3.1` (dev) · OpenAI `gpt-4o-mini` (prod) |
| Email | Resend |
| Auth | Laravel Sanctum (token-based) |
| Infrastructure | Docker · Docker Compose |

---

## 🧠 RAG Pipeline

Pliegos implements a **Retrieval-Augmented Generation** pipeline without orchestration frameworks — built from scratch at a low level for full control over embeddings and prompting.

```
Query
  │
  ▼
EmbeddingService (Strategy)
  │
  ├── local  ──► OllamaEmbeddingStrategy  (nomic-embed-text)
  └── prod   ──► OpenAIEmbeddingStrategy  (text-embedding-3-small)
  │
  ▼
pgvector nearest-neighbor search
(cosine distance via <=> operator)
  │
  ▼
Top-K chunks as context
  │
  ▼
ChatService (Strategy)
  │
  ├── local  ──► OllamaChatStrategy  (llama3.1)
  └── prod   ──► OpenAIChatStrategy  (gpt-4o-mini)
  │
  ▼
Conversational answer
```

---

## 📁 Project Structure

```
app/
├── Http/
│   └── Controllers/
│       ├── Auth/
│       │   └── AuthController.php
│       └── Api/
│           ├── TenderSearchController.php
│           ├── TenderController.php
│           ├── SemanticSearchController.php
│           └── ReportController.php
├── Jobs/
│   ├── GenerateTenderEmbedding.php
│   └── GenerateAndSendReport.php
├── Models/
│   ├── User.php
│   ├── TenderSearch.php
│   ├── Tender.php
│   ├── TenderEmbedding.php
│   └── Report.php
└── Services/
    ├── AI/
    │   ├── EmbeddingService.php
    │   ├── ChatService.php
    │   └── Strategies/
    │       ├── EmbeddingStrategy.php       ← interface
    │       ├── OllamaEmbeddingStrategy.php
    │       ├── OpenAIEmbeddingStrategy.php
    │       ├── ChatStrategy.php            ← interface
    │       ├── OllamaChatStrategy.php
    │       └── OpenAIChatStrategy.php
    ├── Mail/
    │   └── ReportMailService.php
    └── Secop/
        └── SecopService.php
```

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Docker & Docker Compose
- Ollama
- Make

### Installation

```bash
# Clone the repository
git clone git@github.com:BramBit/pliegos.git
cd pliegos

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Start PostgreSQL
make up

# Run migrations
make migrate

# Pull Ollama models
ollama pull nomic-embed-text
ollama pull llama3.1

# Start the server
make serve

# In a separate terminal, start the queue worker
php artisan queue:work
```

### Environment Variables

```env
APP_ENV=local

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pliegos
DB_USERNAME=pliegos_user
DB_PASSWORD=pliegos_pass

QUEUE_CONNECTION=database

OPENAI_API_KEY=        # Only required in production
RESEND_API_KEY=        # Get yours at resend.com
```

---

## 📡 API Reference

### Auth

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register a new user |
| POST | `/api/auth/login` | Login and get token |
| POST | `/api/auth/logout` | Revoke current token |

### Tender Searches

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/searches` | Create a new search |
| GET | `/api/searches` | List user's searches |
| GET | `/api/searches/{id}` | Get a specific search |
| GET | `/api/searches/{id}/tenders` | Fetch tenders for a search |
| POST | `/api/searches/{id}/ask` | Ask a question about tenders |
| POST | `/api/searches/{id}/report` | Generate and send report by email |

### Example Request

```http
POST /api/searches
Authorization: Bearer {token}
Content-Type: application/json

{
  "company": "Tech Solutions SAS",
  "sector": "tecnología",
  "budget_min": 50000000,
  "budget_max": 500000000
}
```

```http
POST /api/searches/1/ask
Authorization: Bearer {token}
Content-Type: application/json

{
  "question": "¿Hay licitaciones para desarrollo de software o sistemas de información?"
}
```

---

## 🔑 Key Design Decisions

**Why RAG without frameworks?**
Building the pipeline from scratch (no LangChain equivalent for PHP) gives full control over chunking strategy, embedding storage, retrieval logic, and prompt engineering. This is the same approach used in production at a real e-learning platform.

**Why the Strategy Pattern?**
Allows seamless switching between Ollama (free, local, ideal for development) and OpenAI (production quality) without touching business logic. Driven purely by `APP_ENV`.

**Why async jobs for embeddings?**
Generating embeddings for 50 tenders synchronously would block the HTTP response for 30+ seconds. Queue jobs decouple ingestion from the user-facing request, keeping response times fast.

**Why pgvector over a dedicated vector DB?**
Keeps the infrastructure simple — one database for relational data and vectors. The HNSW index provides fast approximate nearest-neighbor search without the operational overhead of a separate service.

---

## 📬 Report Example

When a report is requested, the system:

1. Takes the top 10 tenders matching the search criteria
2. Sends them as context to the LLM with a sector-specific prompt
3. Generates an executive summary in Spanish with recommendations
4. Persists the report in the database
5. Sends it to the user's email via Resend

---

## 🗺️ Roadmap

- [ ] OCR pipeline for PDF pliego documents
- [ ] Async ingestion with queue jobs (currently synchronous on first request)
- [ ] Webhook notifications to Slack/Teams
- [ ] Frontend dashboard (Next.js + Tailwind + Shadcn)
- [ ] Scheduled SECOP sync (new tenders daily)
- [ ] Multi-language support

---

## 👨‍💻 Author

**Brayan Mercado Sanmartín**
Backend Developer · Node.js · TypeScript · PHP/Laravel · DDD · AI

[![LinkedIn](https://img.shields.io/badge/LinkedIn-brayan--mercado--sanmartin-blue)](https://linkedin.com/in/brayan-mercado-sanmartin)
[![GitHub](https://img.shields.io/badge/GitHub-BramBit-black)](https://github.com/BramBit)
[![Portfolio](https://img.shields.io/badge/Portfolio-brayanmercado.com-green)](https://brayanmercado.com)
