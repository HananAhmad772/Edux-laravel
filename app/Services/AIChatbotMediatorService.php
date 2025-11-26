<?php

namespace App\Services;

use App\Models\UserProgress;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class AIChatbotMediatorService
{
    private $aiChatbotService;
    
    public function __construct(AIChatbotService $aiChatbotService)
    {
        $this->aiChatbotService = $aiChatbotService;
    }
    
    /**
     * Generate a topic-restricted response from the AI chatbot
     *
     * @param array $messages
     * @param string $userId
     * @param string $roadmapId
     * @return array
     */
    public function generateTopicRestrictedResponse(array $messages, string $userId, ?string $roadmapId = null)
    {
        try {
            // Get user progress to determine current topic
            $userProgress = UserProgress::where('user_id', $userId)->first();
            
            // Get the current topic if user progress exists
            $topicInfo = null;
            if ($userProgress && $userProgress->current_step && $userProgress->roadmap_id) {
                $topicInfo = $this->getCurrentTopicFromRoadmap(
                    $userProgress->roadmap_id, 
                    $userProgress->current_step, 
                    $userProgress->current_topic_index
                );
            }
            
            // Build system prompt with topic restriction
            $systemPrompt = $this->buildSystemPrompt($topicInfo);
            
            // Prepend system prompt to messages
            $enhancedMessages = array_merge([
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ]
            ], $messages);
            
            // Log user message
            $this->logMessage($userId, $roadmapId, $messages, 'user');
            
            // Generate response from AI
            $result = $this->aiChatbotService->generateResponse($enhancedMessages);
            
            if ($result['success']) {
                // Log AI response
                $this->logMessage($userId, $roadmapId, [['content' => $result['data']]], 'assistant');
                
                return [
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('AI Chatbot Mediator Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while generating response',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Build system prompt with topic restriction rules
     *
     * @param string|null $currentTopic
     * @return string
     */
    private function buildSystemPrompt($currentTopic = null)
    {
        $prompt = "You are an AI learning assistant. ";
        
        if ($currentTopic) {
            $prompt .= "You must only answer questions related to the topic: '{$currentTopic}'. ";
            $prompt .= "If a user asks about something outside this topic, you must respond with: ";
            $prompt .= "'This is outside your current topic; would you like to add it to a future roadmap step?'";
        } else {
            $prompt .= "The user hasn't started a specific topic yet. Please guide them to begin their learning journey.";
        }
        
        $prompt .= " Keep your responses educational and helpful.";
        
        return $prompt;
    }
    
    /**
     * Extract the current topic from the roadmap content
     *
     * @param string $roadmapId
     * @param string $currentStep
     * @param int $topicIndex
     * @return array|null
     */
    private function getCurrentTopicFromRoadmap($roadmapId, $currentStep, $topicIndex)
    {
        try {
            $roadmap = \App\Models\StudentRoadmap::find($roadmapId);
            
            if (!$roadmap) {
                return null;
            }
            
            // Use roadmap_json if available, otherwise parse raw content
            $steps = [];
            if ($roadmap->roadmap_json && is_array($roadmap->roadmap_json) && isset($roadmap->roadmap_json['steps'])) {
                foreach ($roadmap->roadmap_json['steps'] as $step) {
                    $stepName = $step['duration'] ?? 'Week 1–2';
                    $steps[$stepName] = $step;
                }
            } else {
                // Fallback to parsing raw content
                $content = $roadmap->roadmap_content;
                $lines = explode("\n", $content);
                $currentStepInParsing = null;
                $currentSection = null;
                $stepPattern = '/^\*\*Week (\d+)–(\d+): (.+)\*\*$/';
                
                foreach ($lines as $line) {
                    $trimmedLine = trim($line);
                    
                    // Check for step headings
                    if (preg_match($stepPattern, $trimmedLine, $matches)) {
                        $stepName = "Week {$matches[1]}–{$matches[2]}";
                        $stepTitle = $matches[3];
                        
                        $currentStepInParsing = [
                            'duration' => $stepName,
                            'title' => $stepTitle,
                            'topics' => [],
                            'tools' => [],
                            'skills' => [],
                            'tasks' => []
                        ];
                        
                        $steps[$stepName] = $currentStepInParsing;
                        $currentSection = null;
                    } else if ($currentStepInParsing) {
                        // Check for section headings
                        if (preg_match('/^-\s*Topics to study:\s*$/i', $trimmedLine)) {
                            $currentSection = 'topics';
                        } else if (preg_match('/^-\s*Tools to use:\s*$/i', $trimmedLine)) {
                            $currentSection = 'tools';
                        } else if (preg_match('/^-\s*Skills learned:\s*$/i', $trimmedLine)) {
                            $currentSection = 'skills';
                        } else if (preg_match('/^-\s*Mini practice tasks or micro-projects:\s*$/i', $trimmedLine)) {
                            $currentSection = 'tasks';
                        } else if ($currentSection && preg_match('/^\s*\*\s+(.+)$/', $line, $matches)) {
                            // Add item to current section
                            $item = trim($matches[1]);
                            if (!empty($item) && $item !== 'None specified') {
                                $steps[$currentStepInParsing['duration']][$currentSection][] = $item;
                            }
                        } else if (preg_match($stepPattern, $trimmedLine)) {
                            // New step heading, reset section
                            $currentSection = null;
                        } else if (preg_match('/^- [^:]+:$/', $trimmedLine)) {
                            // New section heading, reset section
                            $currentSection = null;
                        }
                    }
                }
            }
            
            // Ensure each step has exactly 6 topics
            foreach ($steps as &$step) {
                if (!isset($step['topics']) || !is_array($step['topics'])) {
                    $step['topics'] = [];
                }
                // Pad with placeholders if fewer than 6 topics
                while (count($step['topics']) < 6) {
                    $step['topics'][] = "Topic " . (count($step['topics']) + 1) . " placeholder";
                }
                // Trim to exactly 6 topics if more
                if (count($step['topics']) > 6) {
                    $step['topics'] = array_slice($step['topics'], 0, 6);
                }
            }
            
            // Find the current step and topic
            if (isset($steps[$currentStep]) && isset($steps[$currentStep]['topics'][$topicIndex - 1])) {
                return [
                    'topic' => $steps[$currentStep]['topics'][$topicIndex - 1],
                    'topic_index' => $topicIndex,
                    'step' => $currentStep,
                    'step_title' => $steps[$currentStep]['title'] ?? ''
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error extracting current topic: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log messages to the database
     *
     * @param string $userId
     * @param string|null $roadmapId
     * @param array $messages
     * @param string $role
     * @return void
     */
    private function logMessage($userId, $roadmapId, $messages, $role)
    {
        try {
            foreach ($messages as $message) {
                if (isset($message['content']) && !empty(trim($message['content']))) {
                    Message::create([
                        'user_id' => $userId,
                        'roadmap_id' => $roadmapId,
                        'message_body' => $message['content'],
                        'role' => $role
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error logging message: ' . $e->getMessage(), [
                'user_id' => $userId,
                'roadmap_id' => $roadmapId,
                'role' => $role
            ]);
        }
    }
    
    /**
     * Advance user progress to the next topic
     *
     * @param string $userId
     * @return array
     */
    public function advanceUserProgress($userId)
    {
        try {
            $userProgress = UserProgress::where('user_id', $userId)->first();
            
            if (!$userProgress) {
                return [
                    'success' => false,
                    'message' => 'User progress not found'
                ];
            }
            
            // Update last active timestamp
            $userProgress->last_active_at = now();
            
            // Advance topic index
            $userProgress->current_topic_index++;
            
            // If we've completed all 6 topics in this step, move to the next step
            if ($userProgress->current_topic_index > 6) {
                // For now, we'll just reset to topic 1
                // In a more advanced implementation, we would determine the next step
                $userProgress->current_topic_index = 1;
            }
            
            $userProgress->save();
            
            return [
                'success' => true,
                'message' => 'User progress advanced successfully',
                'data' => $userProgress
            ];
        } catch (\Exception $e) {
            Log::error('Error advancing user progress: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to advance user progress',
                'error' => $e->getMessage()
            ];
        }
    }
}