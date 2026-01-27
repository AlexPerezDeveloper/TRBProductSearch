---
name: document-summarizer
description: |
  Expert document analysis and summarization for extracting key insights efficiently. Análisis experto de documentos y resúmenes para extraer información clave eficientemente.

  **Use when / Usar cuando:**
  - Summarizing documents, articles, or reports (resumir documentos, artículos o reportes)
  - Analyzing PDF, Markdown, or text files (analizar archivos PDF, Markdown o texto)
  - Extracting key points from long documents (extraer puntos clave de documentos largos)
  - Creating executive summaries (crear resúmenes ejecutivos)
  - Identifying main arguments and conclusions (identificar argumentos principales y conclusiones)
  - Condensing technical documentation (condensar documentación técnica)
  - Reviewing and summarizing meeting notes (revisar y resumir notas de reunión)
  - Distilling research papers or whitepapers (destilar papers de investigación o whitepapers)
  - Creating TL;DR for lengthy content (crear TL;DR para contenido extenso)
  - Comparing multiple documents (comparar múltiples documentos)

  **Document types / Tipos de documento:** Technical docs (docs técnicos), research papers (papers de investigación), reports (reportes), articles (artículos), meeting notes (notas de reunión), specs (especificaciones), proposals (propuestas), contracts (contratos), books (libros), manuals (manuales).

  **Output formats / Formatos de salida:** Executive summary (resumen ejecutivo), bullet points (puntos principales), structured outline (esquema estructurado), key takeaways (conclusiones clave), Q&A format (formato preguntas y respuestas), comparison tables (tablas comparativas).
---

# Document Summarizer

## Core Philosophy

**"Capture the essence, not just the words."**

A good summary:

- **Preserves meaning** - Maintains the author's intent accurately
- **Prioritizes** - Highlights what matters most
- **Condenses** - Reduces length dramatically while keeping value
- **Structures** - Organizes information logically
- **Stands alone** - Understandable without the original

## Document Analysis Process

```
┌─────────────────────────────────────────────────────────┐
│ 1. ASSESS DOCUMENT                                      │
│    ├─→ Type: technical, narrative, analytical, etc.    │
│    ├─→ Length: short (<5 pages), medium, long (>20)    │
│    ├─→ Complexity: simple, moderate, highly technical  │
│    └─→ Purpose: inform, persuade, instruct, report     │
├─────────────────────────────────────────────────────────┤
│ 2. IDENTIFY STRUCTURE                                   │
│    ├─→ Find headings, sections, chapters               │
│    ├─→ Locate thesis/abstract/conclusion               │
│    ├─→ Note key figures, tables, diagrams              │
│    └─→ Identify supporting vs. core content            │
├─────────────────────────────────────────────────────────┤
│ 3. EXTRACT KEY INFORMATION                              │
│    ├─→ Main argument or thesis                         │
│    ├─→ Supporting evidence and data                    │
│    ├─→ Conclusions and recommendations                 │
│    └─→ Action items or implications                    │
├─────────────────────────────────────────────────────────┤
│ 4. SYNTHESIZE SUMMARY                                   │
│    ├─→ Choose appropriate format                       │
│    ├─→ Organize by importance or flow                  │
│    ├─→ Use clear, concise language                     │
│    └─→ Verify completeness and accuracy                │
└─────────────────────────────────────────────────────────┘
```

## Summary Formats

### Executive Summary

Best for: Reports, proposals, business documents

```markdown
# Executive Summary

**Document:** [Title]
**Author:** [Author] | **Date:** [Date]
**Purpose:** [Why this document exists]

## Key Findings

[2-3 sentences capturing the main conclusions]

## Critical Points

1. [Most important point]
2. [Second most important]
3. [Third most important]

## Recommendations

- [Primary recommendation]
- [Secondary recommendation]

## Impact

[What this means for the reader/organization]
```

### Bullet Point Summary

Best for: Quick reference, technical docs, meeting notes

```markdown
# Summary: [Document Title]

## Main Topic

- Key point with essential detail
- Another key point
  - Supporting detail if critical
- Third key point

## Secondary Topic

- Relevant points here

## Action Items / Next Steps

- [ ] Specific action
- [ ] Another action
```

### Structured Outline

Best for: Long documents, books, complex reports

```markdown
# Document Outline: [Title]

## Part 1: [Section Name]

### Chapter/Section 1.1

- Main argument: [summary]
- Key evidence: [summary]
- Conclusion: [summary]

### Chapter/Section 1.2

- Main argument: [summary]
  ...

## Key Themes Across Document

1. [Theme 1]
2. [Theme 2]

## Synthesis

[How the parts connect to form the whole argument]
```

### Key Takeaways

Best for: Articles, blog posts, presentations

```markdown
# Key Takeaways: [Title]

**TL;DR:** [1-2 sentence summary]

## 🎯 Main Points

1. **[Point 1 headline]** — Brief explanation
2. **[Point 2 headline]** — Brief explanation
3. **[Point 3 headline]** — Brief explanation

## 💡 Notable Insights

- [Interesting finding or quote]
- [Surprising data point]

## ⚡ Quick Reference

| Topic     | Key Fact |
| --------- | -------- |
| [Topic 1] | [Fact]   |
| [Topic 2] | [Fact]   |
```

### Q&A Format

Best for: Educational content, FAQs, complex explanations

```markdown
# [Document Title] — Q&A Summary

## What is this document about?

[Brief answer]

## What problem does it address?

[Brief answer]

## What are the main conclusions?

[Brief answer with key points]

## What should the reader do with this information?

[Actionable answer]

## What are the limitations or caveats?

[Brief answer]
```

See [references/summary-templates.md](references/summary-templates.md) for more templates.

## Length Guidelines

### Compression Ratios

| Original Length | Target Summary | Ratio |
| --------------- | -------------- | ----- |
| 1-5 pages       | 1/2 - 1 page   | 5:1   |
| 5-20 pages      | 1-2 pages      | 10:1  |
| 20-50 pages     | 2-4 pages      | 15:1  |
| 50-100 pages    | 3-5 pages      | 20:1  |
| 100+ pages      | 5-10 pages     | 20:1+ |

### Adjustment Factors

**Increase length when:**

- Technical content with necessary jargon
- Multiple equally important topics
- Reader needs detailed understanding
- Document has many actionable items

**Decrease length when:**

- Reader only needs overview
- Content is repetitive
- Much content is examples/illustrations
- Time is extremely limited

## Reading Strategies

### Short Documents (<5 pages)

```
1. Read completely once
2. Identify thesis/main point
3. Note supporting arguments
4. Extract conclusions
5. Write summary in one pass
```

### Medium Documents (5-20 pages)

```
1. Skim structure (headings, intro, conclusion)
2. Read introduction carefully
3. Read conclusion carefully
4. Deep read key sections
5. Skim supporting sections
6. Synthesize summary
```

### Long Documents (20+ pages)

```
1. Review table of contents
2. Read abstract/executive summary (if exists)
3. Read introduction and conclusion
4. Identify 3-5 most important sections
5. Deep read critical sections
6. Skim remaining sections
7. Create structured outline
8. Write layered summary
```

### Complex/Technical Documents

```
1. Identify unfamiliar terminology
2. Build glossary of key terms
3. Map relationships between concepts
4. Focus on methodology and findings
5. Separate facts from interpretations
6. Note limitations and caveats
7. Summarize at multiple levels
```

## Extraction Techniques

### Finding the Thesis

Look for statements that:

- Appear in introduction or abstract
- Are repeated in conclusion
- Answer "what is this document arguing?"
- Begin with "This paper/report shows..."
- Are followed by extensive support

### Identifying Key Points

**Structural signals:**

- Numbered lists
- Bold or highlighted text
- Section headings
- "In summary" or "Key points"
- "Most importantly" or "Critical"

**Density signals:**

- Information-rich paragraphs (data, facts)
- Paragraphs with multiple citations
- Conclusions of sections

### Separating Core from Support

| Core Content      | Supporting Content         |
| ----------------- | -------------------------- |
| Main arguments    | Examples and illustrations |
| Key data/findings | Methodology details        |
| Conclusions       | Background context         |
| Recommendations   | Literature review          |
| Call to action    | Acknowledgments            |

## Summary Quality Checklist

### Completeness

- [ ] Main thesis/argument captured
- [ ] All major points included
- [ ] Conclusions accurately represented
- [ ] No critical information omitted

### Accuracy

- [ ] Facts correctly stated
- [ ] No misrepresentation of author's views
- [ ] Numbers and data accurate
- [ ] Nuance preserved where important

### Clarity

- [ ] Understandable without original document
- [ ] Logical flow and organization
- [ ] No unexplained jargon
- [ ] Clear distinction between sections

### Conciseness

- [ ] No unnecessary words
- [ ] No redundant points
- [ ] Appropriate length for purpose
- [ ] Every sentence adds value

## Handling Special Content

### Data and Statistics

```markdown
## Key Statistics

- [Metric]: [Value] ([Context])
- [Metric]: [Value] (up/down X% from [comparison])

Example:

- Conversion rate: 3.2% (up 40% from Q3)
- Customer satisfaction: 87% (industry avg: 72%)
```

### Quotes

Only include quotes that are:

- Particularly eloquent or memorable
- Essential to understanding the argument
- From authoritative sources
- Impossible to paraphrase adequately

```markdown
As [Author] states: "[Quote]"
```

### Technical Terms

```markdown
Key term in **bold** with brief definition on first use.

Example:
The report recommends a **microservices architecture**
(breaking apps into small, independent services) for...
```

### Conflicting Views

```markdown
## Perspectives

| Viewpoint    | Argument  | Evidence Strength    |
| ------------ | --------- | -------------------- |
| [Position A] | [Summary] | Strong/Moderate/Weak |
| [Position B] | [Summary] | Strong/Moderate/Weak |

**Document's stance:** [Which view the author supports and why]
```

## Multi-Document Analysis

When comparing or synthesizing multiple documents:

```markdown
# Comparative Summary

## Documents Analyzed

1. [Title 1] — [Author, Date]
2. [Title 2] — [Author, Date]
3. [Title 3] — [Author, Date]

## Common Themes

- [Theme appearing in multiple docs]
- [Another shared theme]

## Points of Agreement

- [Consensus finding 1]
- [Consensus finding 2]

## Points of Disagreement

| Topic   | Doc 1  | Doc 2  | Doc 3  |
| ------- | ------ | ------ | ------ |
| [Topic] | [View] | [View] | [View] |

## Synthesis

[What we can conclude from all documents together]
```

## Output Customization

### By Audience

**Executive/Leadership:**

- Focus on decisions and impact
- Emphasize recommendations
- Use business language
- Keep very concise

**Technical Team:**

- Include methodology details
- Preserve technical terms
- Note implementation considerations
- Allow more length

**General Audience:**

- Simplify jargon
- Focus on "so what?"
- Use analogies and examples
- Prioritize clarity over completeness

### By Purpose

**Decision Support:**

- Clearly state options
- Present pros/cons
- Highlight risks
- Include recommendations

**Knowledge Transfer:**

- Emphasize key concepts
- Include necessary context
- Note where to find details
- Structure for reference

**Action Planning:**

- Extract all action items
- Note responsibilities
- Include timelines
- Prioritize by importance
