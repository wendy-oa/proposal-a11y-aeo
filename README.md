### AEO + A11Y: DESIGNING FOR HUMANS AND AI AGENTS

# Getting Outsource Accelerator into Google's AI Overview
## 🧠 Overview

This proposal addresses a critical shift in search behavior driven by Google’s AI Overview.

Users are increasingly getting answers directly on search results pages, reducing clicks to websites. As a result, traditional SEO strategies are no longer sufficient.

To remain visible, Outsource Accelerator (OA) must evolve from:
> “Ranking on search results” → **“Being cited by AI systems”**

## 🎯 Objective

Transform OA’s content system (Articles, News, Guides) into:

- Highly structured  
- Easily readable  
- Context-rich  
- Machine-interpretable  

So that OA becomes a **primary source cited in AI-generated search results**.

## 💡 Core Principle

> Accessibility (A11Y) + Answer Engine Optimization (AEO) = One System

Designing for accessibility improves:
- Human usability  
- AI readability  

Both depend on:
- Clear structure  
- Semantic meaning  
- Contextual clarity

## 🛠️ Scope of Work

### 1. Structured Content Templates (AEO + A11Y)

Update WordPress templates for:
- Articles  
- News  
- Guides  

#### New Content Blocks

- **Definition Block**
  - “What is [topic]?”
  - Positioned at top of page
  - Uses `DefinedTerm` schema

- **TL;DR Summary**
  - Key takeaways above the fold
  - Wrapped in `<aside aria-label="Summary">`

- **FAQ Section**
  - 5–8 questions per page
  - Uses `FAQPage` schema

- **Author + Date Block**
  - Clear authorship and publish/update date
  - Uses `Article` schema

---

### 2. Schema Markup Implementation (AEO)

Apply structured data to top-performing pages.

#### Schema Types

| Page Type | Schema |
|----------|--------|
| Article | Article, Author, Organization, BreadcrumbList |
| News | NewsArticle, Author, Organization, BreadcrumbList |
| Guide | HowTo or FAQPage, Article, BreadcrumbList |
| All | DefinedTerm (for key BPO terms) |

---

### 3. Glossary Hub (AEO + A11Y)

Create `/glossary` section using a WordPress Custom Post Type.

#### Page Structure

- `<h1>` — What is [Term]?
- Definition block (with schema)
- Types / Examples (lists)
- Related Terms (internal links)
- FAQ section

#### Initial Terms

- BPO  
- Staff Leasing  
- Offshore Outsourcing  
- Nearshore Outsourcing  
- Virtual Assistant  
- Managed Services  
- Back Office Outsourcing  
- Seat Leasing  
- KPO  
- Outsourcing vs Offshoring  

---

### 4. Related Articles Component (AEO + A11Y)

Updated reusable component for, added time read beside content category (see Figma prototype):
- Articles  
- News  
- Guides  

#### Purpose

- Strengthen internal linking  
- Improve crawlability  
- Reinforce topical authority

## ♿ Accessibility (A11Y) Standards

All updates must follow **WCAG 2.1 AA** guidelines:

- Semantic HTML (`<article>`, `<section>`, `<aside>`, `<nav>`)
- Proper heading hierarchy (H1 → H2 → H3)
- Descriptive `alt` text for all images
- Minimum color contrast compliance
- Visible focus states for interactive elements
- Minimum 16px body text
- Keyboard navigability
- Screen reader-friendly landmarks

## 🤖 AEO Considerations

To improve AI readability and citation likelihood:

- Use structured content blocks consistently
- Add schema markup (JSON-LD)
- Maintain clear definitions and summaries
- Strengthen internal linking
- Use plain, concise language (Grade 8 readability target)

## ⚙️ Dev Handoff Instructions

### 1. Components

Implement reusable blocks for:

- Definition Block
- TL;DR Summary Block
- FAQ Block
- Author Info Block
- Related Articles Component

**Requirements:**
- Must support semantic HTML
- Must allow content team reuse
- Must be flexible across Article, News, Guide templates

### 2. Schema Implementation

- Use **JSON-LD format**
- Inject via:
  - SEO plugin (RankMath / Yoast) OR
  - Direct template insertion

**Validation:**
- Google Rich Results Test
- Ensure no schema errors

### 3. Glossary CPT

- Create Custom Post Type: `glossary`
- Slug: `/glossary/[term]`

**Fields:**
- Title (Term)
- Definition
- Content sections (flexible)
- FAQ block
- Related terms (manual or taxonomy)

### 4. Template Updates

Update:
- Article template
- News template
- Guide template

**Ensure:**
- Blocks are placed consistently
- Semantic structure is preserved
- No heading hierarchy issues

### 5. Accessibility QA

Before release:

- Run Lighthouse audit
- Run axe DevTools
- Manual keyboard navigation test
- Screen reader check (basic) - this should be tested because this is how AI agents read content online

## 📊 Success Metrics

Track impact using:

- **AI Overview appearances** (Google Search Console)
- **Organic CTR**
- **Rich result eligibility**
- **Accessibility score (Lighthouse)**
- **Engagement metrics** (GA4, Clarity)

## 🚀 Rollout Plan

### Phase 1 (Pilot)
- Apply to **Top 10 pages**
- Validate results (2–4 weeks)

### Phase 2 (Scale)
- Roll out to all Articles, News, Guides, Business, and Glossary Pages

## 🧭 Final Note

This is not just an SEO update.

This is a shift toward:
> Designing content as a system that is understood, trusted, and cited by both humans and AI.
