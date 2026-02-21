# Legacy PHP Application Functional Extraction Prompt

## Overview

You are provided with two separate legacy **pure PHP** codebases:

- **`OLD-Customer-application-PHP-code/`** → contains the old **Customer application**
- **`OLD-Merchant-application-PHP-code/`** → contains the old **Merchant application**

These systems:
- Use **direct database connections**
- Render data **directly on PHP pages**
- Do NOT expose API endpoints

Your task is to analyze these legacy applications and extract **all existing functionalities, features, and business logic** in detail.

---

## 1. Primary Objective (Critical)

The **main and mandatory goal** is:

> To identify, document, and clearly describe **every functionality that currently exists** in both legacy applications so that they can be **fully re-implemented in the new system without any loss of behavior or business logic**.

Missing even a single functional behavior is considered a failure of this task.

---

## 2. Code Analysis Scope

Thoroughly analyze **all source code** in both applications:

- Customer Application: `OLD-Customer-application-PHP-code/`
- Merchant Application: `OLD-Merchant-application-PHP-code/`

Including but not limited to:

- PHP pages and includes  
- Form submissions and handlers  
- Database queries and transactions  
- Conditional logic and calculations  
- Session and authentication handling  
- User role–based behavior  
- CRUD operations  
- all modules and components that contribute to the user experience and business processes 
- State transitions and workflow rules  
- Validation logic and constraints  

---

## 3. Documentation Deliverables

Produce **two separate functional specification documents**:

### 📘 Document 1: Customer Application  
Source: `OLD-Customer-application-PHP-code/`

### 📕 Document 2: Merchant Application  
Source: `OLD-Merchant-application-PHP-code/`

Each document must list and explain:

- Every **feature and functionality**  
- All **business rules and decision logic**  
- Step-by-step **user workflows**  
- Page-level behaviors  
- Data dependencies and effects  
- Validation and restriction rules  

---

## 4. Documentation Requirements

For each identified functionality, document:

- Feature name  
- Description of what it does  
- Who can use it (user role)  
- Inputs (forms, buttons, parameters)  
- Processing logic  
- Output/result  
- Database impact (tables affected)  
- Conditions and edge cases  

---

## 5. Purpose of the Documentation

These documents will be used as:

- The **functional blueprint** for building the new Customer and Merchant applications  
- The **single source of truth** for existing system behavior  
- A safeguard to ensure **no functional regression** during migration  

Therefore:

- ✅ Everything that exists must be captured  
- ✅ Nothing new must be invented  
- ✅ Nothing existing must be ignored  
- ✅ Logic must be described in business terms, not just code  

---

## 6. Documentation Style

- Functional and business-oriented language  
- Clear structure and headings  
- No raw code dumps  
- No speculative features  
- No architectural redesign  

---

## 7. Prohibited Actions

- ❌ Do NOT redesign the system  
- ❌ Do NOT modernize it  
- ❌ Do NOT convert it to APIs  
- ❌ Do NOT simplify or omit logic  
- ❌ Do NOT assume undocumented behavior  

Only describe what is verifiably present in the legacy code.

---

## Output Format

- Two separate Markdown documents:
  - `customer-functional-spec.md`
  - `merchant-functional-spec.md`

Each must be complete and standalone.